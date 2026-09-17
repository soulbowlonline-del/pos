#!/bin/bash
# Differential test for customer/uploadbill.
#
# This one receives a real multipart upload, writes it under the web root and
# posts it on to a remote endpoint, so there are four things to compare: the
# response, the recorded outbound calls, the database state, and the file that
# landed on disk. The remote post goes through the outbound stub; the local
# write is real, and the fixture clears it between calls.
#
# Two cases deliberately omit the id or the file. Yii 1 reads $_POST['id'] and
# $_FILES['file']['name'] unchecked, so those emit PHP 8 undefined-key warnings
# on both stacks - that is the behaviour under test, not an oversight.
BASE=http://127.0.0.1:8084
UPLOADS=/root/pos/pos83/uploadbills
FIX=/root/pos/fixtures
PASS=0; FAIL=0

reset() {
  docker exec pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE -e "
    UPDATE tbl_customer SET contact_no=\"9990001234\" WHERE id=9990001;
    DELETE FROM tbl_outbound_stub_log;
    DELETE FROM tbl_whatsapp_logs WHERE number IN (\"9990001234\") OR number=\"\" OR number IS NULL;"' >/dev/null 2>&1
  rm -f "$UPLOADS"/Bill9990001*.pdf
}

norm() { sed -E 's/stub-[0-9a-f]+/stub-<FP>/g'; }

outbound() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(channel,\" |\",target,\" |\",payload) FROM tbl_outbound_stub_log ORDER BY id;"' 2>/dev/null | norm
}
dbstate() {
  docker exec -i pos-mysql-8 sh -c 'mysql -uroot -p$MYSQL_ROOT_PASSWORD -N $MYSQL_DATABASE -e "
    SELECT CONCAT(\"log:\",number,\"|\",status,\"|\",template_name,\"|\",IFNULL(user_id,\"-\"),\"|\",IFNULL(computer_name,\"-\")) FROM tbl_whatsapp_logs WHERE number IN (\"9990001234\") OR number=\"\" OR number IS NULL ORDER BY id;"' 2>/dev/null | norm
}
# what actually landed on disk, by name and content
disk() {
  for f in "$UPLOADS"/Bill9990001*.pdf; do
    [ -e "$f" ] || continue
    echo "file:$(basename "$f")|$(md5sum "$f" | cut -d" " -f1)"
  done
}

run_case() {
  local name="$1" file="$2"; shift 2
  reset; local r1; r1=$(curl -sS --max-time 120 "$@" ${file:+-F "file=@$FIX/bill.pdf;filename=$file"} "$BASE/api/customer/uploadbill" | norm)
  local o1; o1=$(outbound); local s1; s1=$(dbstate); local d1; d1=$(disk)
  reset; local r2; r2=$(curl -sS --max-time 120 "$@" ${file:+-F "file=@$FIX/bill.pdf;filename=$file"} "$BASE/v2/api/customer/uploadbill" | norm)
  local o2; o2=$(outbound); local s2; s2=$(dbstate); local d2; d2=$(disk)

  if [ "$r1" = "$r2" ] && [ "$o1" = "$o2" ] && [ "$s1" = "$s2" ] && [ "$d1" = "$d2" ]; then
    printf "  %-40s OK  (response, outbound, state, disk)\n" "$name"; PASS=$((PASS+1))
  else
    printf "  %-40s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$r1" != "$r2" ] && { echo "      resp yii1: $(echo "$r1" | head -c 300)"; echo "      resp yii2: $(echo "$r2" | head -c 300)"; }
    [ "$o1" != "$o2" ] && { echo "      out  yii1: $(echo "$o1" | head -c 300)"; echo "      out  yii2: $(echo "$o2" | head -c 300)"; }
    [ "$s1" != "$s2" ] && { echo "      db   yii1: $(echo $s1|tr '\n' ' ')"; echo "      db   yii2: $(echo $s2|tr '\n' ' ')"; }
    [ "$d1" != "$d2" ] && { echo "      disk yii1: $(echo $d1|tr '\n' ' ')"; echo "      disk yii2: $(echo $d2|tr '\n' ' ')"; }
  fi
}

# Two cases make Yii 1 read a key that is not there, and both stacks turn the
# warning into a 500. Their bodies cannot be compared - each framework renders
# its own error page - so these compare the status code and every side effect
# instead. Verified separately: both report the same missing key ("file" and
# "id" respectively), so the failure has the same cause on both stacks.
run_error_case() {
  local name="$1" file="$2"; shift 2
  reset; local c1; c1=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 "$@" ${file:+-F "file=@$FIX/bill.pdf;filename=$file"} "$BASE/api/customer/uploadbill")
  local o1; o1=$(outbound); local s1; s1=$(dbstate); local d1; d1=$(disk)
  reset; local c2; c2=$(curl -sS -o /dev/null -w "%{http_code}" --max-time 120 "$@" ${file:+-F "file=@$FIX/bill.pdf;filename=$file"} "$BASE/v2/api/customer/uploadbill")
  local o2; o2=$(outbound); local s2; s2=$(dbstate); local d2; d2=$(disk)

  if [ "$c1" = "$c2" ] && [ "$o1" = "$o2" ] && [ "$s1" = "$s2" ] && [ "$d1" = "$d2" ]; then
    printf "  %-40s OK  (status %s, outbound, state, disk)\n" "$name" "$c1"; PASS=$((PASS+1))
  else
    printf "  %-40s MISMATCH\n" "$name"; FAIL=$((FAIL+1))
    [ "$c1" != "$c2" ] && echo "      status yii1: $c1   yii2: $c2"
    [ "$o1" != "$o2" ] && { echo "      out  yii1: $(echo "$o1" | head -c 300)"; echo "      out  yii2: $(echo "$o2" | head -c 300)"; }
    [ "$s1" != "$s2" ] && { echo "      db   yii1: $(echo $s1|tr '\n' ' ')"; echo "      db   yii2: $(echo $s2|tr '\n' ' ')"; }
    [ "$d1" != "$d2" ] && { echo "      disk yii1: $(echo $d1|tr '\n' ' ')"; echo "      disk yii2: $(echo $d2|tr '\n' ' ')"; }
  fi
}

echo "=== customer/uploadbill differential (outbound stubbed) ==="
run_case "plain bill"                 "Bill9990001.pdf"          -F id=9990001
run_case "reprint in the file name"   "Bill9990001_Reprint.pdf"  -F id=9990001
run_case "refund in the file name"    "Bill9990001-Refund.pdf"   -F id=9990001
run_case "uppercase REPRINT"          "Bill9990001_REPRINT.pdf"  -F id=9990001
run_case "refund takes second place"  "Bill9990001_Refund.pdf"   -F id=9990001
run_case "with user_id + computer"    "Bill9990001.pdf"          -F id=9990001 -F user_id=9990002 -F computer_name=till-03
run_case "unknown customer id"        "Bill9990001.pdf"          -F id=98765432
run_error_case "no file, id present"  ""                         -F id=9990001
run_error_case "file, no id"          "Bill9990001.pdf"          -F other=1
echo
echo "  passed: $PASS   mismatched: $FAIL"
