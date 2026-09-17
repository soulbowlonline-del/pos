# PHP 8 fragility sweep

Static analysis (PHPStan level 2) across `protected/`, looking for one family of
defect: reads of undefined variables.

PHP 5.6 tolerated these. PHP 8 reports them, and Yii 1's error handler turns any
reported error into a 500 - so each one is a page that fails on particular data,
while passing a lint and looking fine in review.

Four such faults were found one at a time during the Yii 2 API port
(`round(null)` on orders with no lines, a null date of birth in `GetAge()`,
`$json_list` in two report actions, `$bill_prefix` in the B2B summary). This
sweep looks for the rest of them systematically.

## Definite: read, never assigned anywhere in scope

All fixed - see the commit that added this file.

  - protected/controllers/EmpController.php:142:Undefined variable: $password
  - protected/controllers/MrnDetailController.php:503:Undefined variable: $existmrs
  - protected/controllers/MrnDetailController.php:504:Undefined variable: $existmrs
  - protected/controllers/MrnDetailController.php:505:Undefined variable: $existmrs
  - protected/controllers/MrnDetailController.php:506:Undefined variable: $existmrs
  - protected/controllers/MrnDetailController.php:751:Undefined variable: $mrsid
  - protected/controllers/PurchaseOrderDetailController.php:641:Undefined variable: $mrsid
  - protected/controllers/PurchaseOrderDetailController.php:896:Undefined variable: $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:900:Undefined variable: $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:904:Undefined variable: $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:908:Undefined variable: $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:912:Undefined variable: $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:916:Undefined variable: $mrnIdAll
  - protected/controllers/PurchaseOrderDetailController.php:919:Undefined variable: $mrsIdAll
  - protected/controllers/PurchaseOrderDetailController.php:922:Undefined variable: $mrsIdAll
  - protected/controllers/UserController.php:359:Undefined variable: $objects
  - protected/models/MrnDetail.php:215:Undefined variable: $cssClass
  - protected/models/OrderItem.php:756:Undefined variable: $month
  - protected/models/OrderItem.php:757:Undefined variable: $tank
  - protected/models/OrderItem.php:758:Undefined variable: $year
  - protected/models/OrderItem.php:852:Undefined variable: $month
  - protected/models/OrderItem.php:864:Undefined variable: $month
  - protected/models/User.php:255:Undefined variable: $headers
  - protected/models/User.php:280:Undefined variable: $headers
  - protected/models/User.php:303:Undefined variable: $headers
  - protected/models/User.php:333:Undefined variable: $headers
  - protected/models/User.php:391:Undefined variable: $headers

## Conditional: assigned on some paths only

**Not yet triaged.** Each needs reading in context - many will be harmless (a
variable assigned in every branch that matters), and some will be the same
defect as the four above. Listed here so the work is visible rather than
rediscovered one 500 at a time.

  - protected/components/GxActiveRecord.php:466:Variable $relatedFkName might not be defined.
  - protected/components/GxActiveRecord.php:467:Variable $thisFkName might not be defined.
  - protected/components/GxActiveRecord.php:472:Variable $relatedFkName might not be defined.
  - protected/components/GxActiveRecord.php:497:Variable $relatedFkName might not be defined.
  - protected/components/GxActiveRecord.php:497:Variable $thisFkName might not be defined.
  - protected/components/GxActiveRecord.php:500:Variable $relatedFkName might not be defined.
  - protected/components/GxActiveRecord.php:500:Variable $thisFkName might not be defined.
  - protected/components/GxActiveRecord.php:685:Variable $transacted might not be defined.
  - protected/components/GxActiveRecord.php:686:Variable $transaction might not be defined.
  - protected/components/GxActiveRecord.php:750:Variable $controllerID might not be defined.
  - protected/components/views/commentPortlet.php:2:Variable $this might not be defined.
  - protected/components/views/commentPortlet.php:17:Variable $this might not be defined.
  - protected/components/views/commentPortlet.php:19:Variable $this might not be defined.
  - protected/components/views/commentPortlet.php:35:Variable $this might not be defined.
  - protected/components/views/commentPortlet.php:55:Variable $this might not be defined.
  - protected/components/views/commentPortlet.php:63:Variable $this might not be defined.
  - protected/controllers/AdvancePaymentController.php:74:Variable $oldbal might not be defined.
  - protected/controllers/AdvancePaymentController.php:75:Variable $oldpay might not be defined.
  - protected/controllers/B2BPurchaseBillDetailController.php:97:Variable $billmodel might not be defined.
  - protected/controllers/B2BPurchaseBillDetailController.php:208:Variable $itemdetail might not be defined.
  - protected/controllers/B2BPurchaseBillDetailController.php:307:Variable $purchaseBill might not be defined.
  - protected/controllers/B2BPurchaseBillDetailController.php:539:Variable $qtys might not be defined.
  - protected/controllers/B2BPurchaseBillDetailController.php:636:Variable $qtys might not be defined.
  - protected/controllers/B2bPurchaseBillController.php:529:Variable $billno might not be defined.
  - protected/controllers/B2bPurchaseBillController.php:529:Variable $invoicedate might not be defined.
  - protected/controllers/ItemController.php:201:Variable $adjusted_qty might not be defined.
  - protected/controllers/ItemController.php:476:Variable $last_id might not be defined.
  - protected/controllers/ItemController.php:639:Variable $outlet might not be defined.
  - protected/controllers/ItemController.php:682:Variable $outlet might not be defined.
  - protected/controllers/ItemController.php:693:Variable $outlet might not be defined.
  - protected/controllers/ItemController.php:744:Variable $outlet might not be defined.
  - protected/controllers/ItemController.php:764:Variable $outlet might not be defined.
  - protected/controllers/ItemController.php:845:Variable $outlet might not be defined.
  - protected/controllers/ItemController.php:881:Variable $outlet might not be defined.
  - protected/controllers/ItemExpireItemController.php:121:Variable $total_amt might not be defined.
  - protected/controllers/ItemReturnItemController.php:142:Variable $itemdetail might not be defined.
  - protected/controllers/ItemStockController.php:101:Variable $item might not be defined.
  - protected/controllers/MrnDetailController.php:357:Variable $itemdetail might not be defined.
  - protected/controllers/MrnDetailController.php:849:Variable $qtys might not be defined.
  - protected/controllers/MrnDetailController.php:918:Variable $qtys might not be defined.
  - protected/controllers/MrnDetailController.php:1073:Variable $status might not be defined.
  - protected/controllers/MrsDetailController.php:246:Variable $itemdetail might not be defined.
  - protected/controllers/MrsDetailController.php:829:Variable $qtys might not be defined.
  - protected/controllers/MrsDetailController.php:831:Variable $qtys might not be defined.
  - protected/controllers/MrsDetailController.php:865:Variable $qtys might not be defined.
  - protected/controllers/MrsDetailController.php:866:Variable $qtys might not be defined.
  - protected/controllers/OnlineOrderController.php:345:Variable $user might not be defined.
  - protected/controllers/OrderController.php:265:Variable $order_id might not be defined.
  - protected/controllers/OrderController.php:424:Variable $oldcgst might not be defined.
  - protected/controllers/OrderController.php:427:Variable $oldcgst might not be defined.
  - protected/controllers/OrderController.php:431:Variable $oldcgst might not be defined.
  - protected/controllers/PurchaseBillDetailController.php:199:Variable $itemdetail might not be defined.
  - protected/controllers/PurchaseBillDetailController.php:298:Variable $purchaseBill might not be defined.
  - protected/controllers/PurchaseBillDetailController.php:516:Variable $qtys might not be defined.
  - protected/controllers/PurchaseBillDetailController.php:581:Variable $qtys might not be defined.
  - protected/controllers/PurchaseBillDetailController_11_1_22_kara.php:181:Variable $itemdetail might not be defined.
  - protected/controllers/PurchaseBillDetailController_11_1_22_kara.php:388:Variable $purchaseBill might not be defined.
  - protected/controllers/PurchaseBillDetailController_11_1_22_kara.php:615:Variable $qtys might not be defined.
  - protected/controllers/PurchaseBillDetailController_11_1_22_kara.php:692:Variable $qtys might not be defined.
  - protected/controllers/PurchaseOrderDetailController.php:753:Variable $qtys might not be defined.
  - protected/controllers/PurchaseOrderDetailController.php:841:Variable $vendoruser might not be defined.
  - protected/controllers/PurchaseOrderDetailController.php:857:Variable $qtys might not be defined.
  - protected/controllers/PurchaseOrderDetailController.php:989:Variable $status might not be defined.
  - protected/models/B2bOrder.php:529:Variable $columns might not be defined.
  - protected/models/B2bOrder.php:671:Variable $columns might not be defined.
  - protected/models/B2bPurchaseBill.php:371:Variable $columns might not be defined.
  - protected/models/B2bPurchaseBill.php:446:Variable $columns might not be defined.
  - protected/models/B2bPurchaseBill.php:606:Variable $columns might not be defined.
  - protected/models/B2bPurchaseBill.php:747:Variable $addqty might not be defined.
  - protected/models/B2bPurchaseBillDetail.php:460:Variable $columns might not be defined.
  - protected/models/B2bPurchaseBillDetail.php:688:Variable $columns might not be defined.
  - protected/models/B2bPurchaseBillDetail.php:869:Variable $columns might not be defined.
  - protected/models/B2bPurchaseBillDetail.php:1421:Variable $columns might not be defined.
  - protected/models/Customer.php:291:Variable $columns might not be defined.
  - protected/models/ItemCategory.php:97:Variable $columns might not be defined.
  - protected/models/ItemCompany.php:159:Variable $columns might not be defined.
  - protected/models/ItemExpire.php:86:Variable $columns might not be defined.
  - protected/models/ItemReturn.php:78:Variable $columns might not be defined.
  - protected/models/ItemReturnItem.php:431:Variable $columns might not be defined.
  - protected/models/ItemTax.php:157:Variable $columns might not be defined.
  - protected/models/OnlineOrder.php:129:Variable $json_list might not be defined.
  - protected/models/OnlineOrder.php:142:Variable $json_list might not be defined.
  - protected/models/Order.php:655:Variable $columns might not be defined.
  - protected/models/Order.php:804:Variable $columns might not be defined.
  - protected/models/OrderItem.php:475:Variable $columns might not be defined.
  - protected/models/OrderItem.php:612:Variable $columns might not be defined.
  - protected/models/OrderItem.php:753:Variable $columns might not be defined.
  - protected/models/OrderItem.php:845:Variable $last_sale might not be defined.
  - protected/models/OrderItem.php:917:Variable $array12 might not be defined.
  - protected/models/OrderItem.php:932:Variable $diff might not be defined.
  - protected/models/OrderItem.php:945:Variable $array16 might not be defined.
  - protected/models/OrderItem.php:1063:Variable $columns might not be defined.
  - protected/models/OrderItem.php:1158:Variable $columns might not be defined.
  - protected/models/OrderItem.php:1278:Variable $columns might not be defined.
  - protected/models/OrderRefundItem.php:133:Variable $columns might not be defined.
  - protected/models/Order_15_7_21.php:536:Variable $columns might not be defined.
  - protected/models/Order_15_7_21.php:665:Variable $columns might not be defined.
  - protected/models/PaymentReport.php:68:Variable $columns might not be defined.
  - protected/models/PurchaseBill.php:371:Variable $columns might not be defined.
  - protected/models/PurchaseBill.php:512:Variable $addqty might not be defined.
  - protected/models/PurchaseBillDetail.php:347:Variable $columns might not be defined.
  - protected/models/ScannedItems.php:124:Variable $columns might not be defined.
  - protected/models/StockAdjustLog.php:147:Variable $columns might not be defined.
  - protected/models/Tax.php:120:Variable $columns might not be defined.
  - protected/models/Vendor.php:362:Variable $columns might not be defined.
  - protected/models/WhatsappLogs.php:103:Variable $columns might not be defined.
  - protected/modules/api/controllers/EmpController.php:243:Variable $list might not be defined.
  - protected/modules/api/controllers/EmpController.php:275:Variable $list might not be defined.
  - protected/modules/api/controllers/ItemController.php:118:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:119:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:120:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:121:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:122:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:123:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:125:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:125:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:126:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:126:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:127:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:127:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:128:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:128:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:131:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:134:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:137:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:138:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:145:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:146:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:204:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:206:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:238:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:268:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:269:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:274:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:283:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:293:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:331:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:337:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:346:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:352:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:430:Variable $outlet_id might not be defined.
  - protected/modules/api/controllers/ItemController.php:1038:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1039:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1040:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1041:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1042:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1043:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1045:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:1045:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1046:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:1046:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1047:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:1047:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1048:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:1048:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1051:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1054:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1057:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1058:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1065:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1066:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1124:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1127:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1160:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1190:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1191:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1196:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1205:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1215:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1254:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1257:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1261:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1270:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1279:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:1363:Variable $outlet might not be defined.
  - protected/modules/api/controllers/ItemController.php:1406:Variable $outlet might not be defined.
  - protected/modules/api/controllers/ItemController.php:1417:Variable $outlet might not be defined.
  - protected/modules/api/controllers/ItemController.php:1468:Variable $outlet might not be defined.
  - protected/modules/api/controllers/ItemController.php:1489:Variable $outlet might not be defined.
  - protected/modules/api/controllers/ItemController.php:1573:Variable $outlet might not be defined.
  - protected/modules/api/controllers/ItemController.php:1609:Variable $outlet might not be defined.
  - protected/modules/api/controllers/ItemController.php:1815:Variable $outlet might not be defined.
  - protected/modules/api/controllers/ItemController.php:2090:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2091:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2092:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2093:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2094:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2095:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2097:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:2097:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2098:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:2098:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2099:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:2099:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2100:Variable $customer might not be defined.
  - protected/modules/api/controllers/ItemController.php:2100:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2103:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2109:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2111:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2112:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2119:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2120:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2178:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2181:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2189:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2217:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2218:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2223:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2232:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2242:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2281:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2284:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2288:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2297:Variable $order might not be defined.
  - protected/modules/api/controllers/ItemController.php:2306:Variable $order might not be defined.
  - protected/modules/api/controllers/OrderController.php:374:Variable $taxarr might not be defined.
  - protected/modules/api/controllers/OrderController.php:805:Variable $refundmodel might not be defined.
  - protected/modules/api/controllers/OrderController.php:806:Variable $refundmodel might not be defined.
  - protected/modules/api/controllers/TallyController.php:95:Variable $gst might not be defined.
  - protected/modules/api/controllers/TallyController.php:101:Variable $cgst might not be defined.
  - protected/modules/api/controllers/TallyController.php:102:Variable $sgst might not be defined.
  - protected/modules/api/controllers/TallyController.php:104:Variable $igst might not be defined.
  - protected/modules/api/controllers/TallyController.php:105:Variable $total_amt might not be defined.
  - protected/modules/api/controllers/TallyController.php:419:Variable $gst might not be defined.
  - protected/modules/api/controllers/TallyController.php:425:Variable $cgst might not be defined.
  - protected/modules/api/controllers/TallyController.php:426:Variable $sgst might not be defined.
  - protected/modules/api/controllers/TallyController.php:428:Variable $igst might not be defined.
  - protected/modules/api/controllers/TallyController.php:429:Variable $total_amt might not be defined.
  - protected/modules/api/views/default/index.php:2:Variable $this might not be defined.
  - protected/modules/api/views/default/index.php:3:Variable $this might not be defined.
  - protected/modules/api/views/default/index.php:6:Variable $this might not be defined.
  - protected/modules/api/views/default/index.php:6:Variable $this might not be defined.
  - protected/modules/api/views/default/index.php:9:Variable $this might not be defined.
  - protected/modules/api/views/default/index.php:10:Variable $this might not be defined.
  - protected/modules/api/views/default/index.php:11:Variable $this might not be defined.
  - protected/modules/backup/views/default/_list.php:1:Variable $this might not be defined.
  - protected/modules/backup/views/default/_list.php:3:Variable $dataProvider might not be defined.
  - protected/modules/backup/views/default/index.php:2:Variable $this might not be defined.
  - protected/modules/backup/views/default/index.php:8:Variable $this might not be defined.
  - protected/modules/backup/views/default/index.php:9:Variable $this might not be defined.
  - protected/modules/backup/views/default/index.php:27:Variable $this might not be defined.
  - protected/modules/backup/views/default/index.php:28:Variable $dataProvider might not be defined.
  - protected/modules/backup/views/default/restore.php:2:Variable $this might not be defined.
  - protected/modules/backup/views/default/restore.php:6:Variable $this might not be defined.
  - protected/modules/backup/views/default/upload.php:2:Variable $this might not be defined.
  - protected/modules/backup/views/default/upload.php:6:Variable $this might not be defined.
  - protected/modules/backup/views/default/upload.php:11:Variable $this might not be defined.
  - protected/modules/backup/views/default/upload.php:18:Variable $model might not be defined.
  - protected/modules/backup/views/default/upload.php:19:Variable $model might not be defined.
  - protected/modules/backup/views/default/upload.php:20:Variable $model might not be defined.
  - protected/modules/backup/views/default/upload.php:24:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/admin_layout.php:6:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/admin_layout.php:670:Variable $permission might not be defined.
  - protected/modules/backup/views/layouts/admin_layout.php:737:Variable $content might not be defined.
  - protected/modules/backup/views/layouts/column1.php:1:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/column1.php:7:Variable $content might not be defined.
  - protected/modules/backup/views/layouts/column1.php:14:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/column2.php:1:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/column2.php:4:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/column2.php:4:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/column2.php:11:Variable $content might not be defined.
  - protected/modules/backup/views/layouts/column2.php:17:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/column3.php:1:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/column3.php:10:Variable $content might not be defined.
  - protected/modules/backup/views/layouts/column3.php:21:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/main.php:17:Variable $this might not be defined.
  - protected/modules/backup/views/layouts/main.php:91:Variable $content might not be defined.

## Excluded as noise

- `Access to an undefined property <Model>::$x` - Yii 1 resolves ActiveRecord
  attributes through `__get` at runtime, so static analysis cannot see them.
  ~1,400 findings, none actionable.
- `Undefined variable: $this` - analyser artefact.

## Reproducing

```bash
docker run --rm -v $PWD:/app -v /root/pos/tools:/tools -w /app php:8.3-cli \
  php -d memory_limit=3G /tools/phpstan.phar analyse --no-progress -c phpstan.neon
```
