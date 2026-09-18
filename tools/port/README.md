# The web-UI port pipeline

These are the generators described in `docs/web-ui-port.md`. They read the
Yii 1 application in `protected/` and write the Yii 2 one in `app2/`.

They run on the server, from `/root/pos`, where the differential harness lives:

    cd /root/pos
    python3 batch_port.py UserRole City State

`batch_port.py` is the entry point. For each controller it runs the three
generators, brings the models its pages display up to the display contract,
compares every page against Yii 1, and adds the controller to `Ui::PORTED`
**only if it matches**. One that differs is left on Yii 1.

| | |
|---|---|
| `port_model.py <Model>` | the model, from the giix `_base` class plus the hand-written methods on the concrete one. `--presentation-only` adds only the display contract to a model the API port already wrote. |
| `port_controller.py <Model>` | the controller, translated from the Yii 1 one. |
| `port_views.py <controller>` | the views. `--only <file>` for one, `--keep-existing` to leave anything already there alone. |
| `setported.py <ctrl>...` | sets `Ui::PORTED` by hand, for working on a controller the batch has rejected. |
| `uilogin.sh` | creates the test administrator and logs in, leaving a session in `/tmp/uic.txt`. Remove the account with `ui_teardown.sql` afterwards. |
| `probe_errors.sh <ctrl>...` | prints the first error each ported page returns. |

None of them guess. Anything a generator does not recognise is left exactly as
it was and reported, so it fails on the first request rather than rendering a
page that is quietly wrong. Treat every `NOTE:` and `UNCONVERTED` line as work
still to do.

Two rules the pipeline enforces, both learned the hard way:

  - It never overwrites a view that git tracks. `app2/views/item/_pdf.php` is
    the hand-ported invoice template the punchorder API renders; regenerating
    it mechanically broke a suite that had been green for weeks, and every
    database row still matched - only the PDF was missing.
  - A controller that does not compare clean does not ship.
