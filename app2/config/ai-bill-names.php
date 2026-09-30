<?php
/**
 * What particular vendors call things on their bills.
 *
 * "Read a vendor bill" on the GRN screen pairs a bill's lines with the GRN's
 * by barcode, name, rate and quantity (GrnBillFill). Some vendors print
 * their own names, with nothing in common with the item master's, and count
 * in a unit of their own. What the owner has said such a vendor means is
 * written here, once, and holds for every bill of that vendor.
 *
 * Keyed by vendor id (tbl_vendor.id):
 *   units  the vendor's unit, as printed in the bill's unit column => pieces
 *          in one. Letters only, any case. When a line's unit is listed, its
 *          quantity and price are always converted with it.
 *   items  the vendor's name for an item => the item's barcode. Matched on
 *          the words, ignoring case and punctuation; the bill's line may say
 *          more (a note written beside it). A line matched here is that item,
 *          as surely as if its barcode were printed.
 *
 *   reading  a sentence added to what Claude is told when it reads this
 *          vendor's bills: what is particular about how they look.
 *   pack_size_is_certain  true when the pieces in a case are stated on each
 *          line of this vendor's bills (see reading), so that the figure read
 *          is used as it is rather than weighed against the GRN's rate. A
 *          figure that contradicts a pack size printed in the item's name is
 *          still weighed, and flagged.
 *
 * Nothing here is a secret. To add a vendor: its id, then the names exactly
 * as its bills print them and the barcodes from the item master.
 */
return [
    // AMARJIT SINGH - Verka milk. Owner, 30 Sep 2026: "crat means crate and
    // one crate has 24pcs of qty. SM Milk 500 ML means Verka Milk Green, DTM
    // Milk 500ML means Verka Milk Yellow and Full Cream Milk 500ML means
    // Verka Milk Gold ... this is true everytime the bill comes".
    310 => [
        'units' => ['crat' => 24, 'crate' => 24, 'crates' => 24],
        'items' => [
            'SM Milk 500ml' => '8901826601209',          // VERKA MILK GREEN 500ML
            'DTM Milk 500ml' => '8901826601506',         // VERKA MILK YELLOW 500ML
            'Full Cream Milk 500ml' => '8901826601100',  // VERKA MILK GOLD 500ML
        ],
    ],

    // RANA ENTERPRISES - Catch water and soda, billed by the BOX. Owner,
    // 30 Sep 2026: "in each box the qty is written in black next to the
    // boxes. This is true for every bill of this vendor. Ignore the Blue
    // handwritten number on the left."
    617 => [
        'reading' => 'On this vendor\'s bills the number of pieces in one box is written by hand, in black ink, right beside the unit '
            . '(for example "BOX 24"). Give that handwritten number as the line\'s pack_size. '
            . 'Ignore the numbers and ticks written by hand in blue at the left edge of the lines: they are not part of the bill.',
        'pack_size_is_certain' => true,
    ],

    // DUA ENTERPRISES - Verka dahi, paneer, kheer and lassi, billed by the
    // piece. Owner, 30 Sep 2026: "map Dua Enterprises curd to Verka Dahi".
    // The names are as its bill GST/26-27/1098 prints them, with and without
    // the space its printing leaves out; paneer and kheer are on that bill
    // too and were paired rightly without this. Its names for the lassis are
    // not known yet.
    226 => [
        'items' => [
            'Curd 170gm' => '8901826550934',    // VERKA DAHI 170GM ("Curd 170gm TM")
            'Curd170gm' => '8901826550934',
            'Curd 350gm' => '8901826803702',    // VERKA DAHI 350GM ("Curd350gm")
            'Curd350gm' => '8901826803702',
            'Paneer 200gm' => '8901826334053',  // VERKA PANEER 200GM ("Paneer200gm")
            'Paneer200gm' => '8901826334053',
            'Kheer 200gm' => '8901826302076',   // VERKA KHEER 200GM
            'Kheer200gm' => '8901826302076',
        ],
    ],
];
