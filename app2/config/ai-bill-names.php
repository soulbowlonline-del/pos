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
];
