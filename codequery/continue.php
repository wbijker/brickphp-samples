<?php

class ProductUnit
{
    function __construct(public int $unit, public string $form)
    {
    }
}

$products = Query::values([
    new ProductUnit(0, "Solid"),
    new ProductUnit(1, "Solid"),
    new ProductUnit(2, "Solid"),
    new ProductUnit(3, "Solid"),
    new ProductUnit(4, "Solid"),
    new ProductUnit(5, "Solid"),
    new ProductUnit(6, "Solid"),
    new ProductUnit(7, "Solid"),
    new ProductUnit(8, "Solid"),
    new ProductUnit(9, "Solid"),
    new ProductUnit(9, "Solid"),
    new ProductUnit(10, "Liquid"),
    new ProductUnit(11, "Liquid"),
    new ProductUnit(12, "Liquid"),
    new ProductUnit(13, "Liquid"),
    new ProductUnit(14, "Liquid"),
    new ProductUnit(15, "Liquid"),
    new ProductUnit(16, "Liquid"),
    new ProductUnit(17, "Liquid"),
    new ProductUnit(18, "Liquid"),
    new ProductUnit(19, "Liquid"),
    new ProductUnit(20, "Liquid"),
]);

// $pfr->columns() == $pfr->data->property('Columns')

$expand = Query::scoped(fn(PlayFieldRevisions $pfr) => 
    Query::from($pfr)
        ->lateralJoin($pfr->columns()->arrayElements()) // return JsonColumn
        ->where($pfr->columns()->typeof()->equals("array"))
        ->select(fn(JsonColumn $json) => new ColumnExpand(
            playFieldRevisionId: $pfr->id,
            unit: $json->proprety('Unit')->toInt(),
            productId: $json->property('ProdudctId')->toInt()
        ));

$query = Query::scoped([$expand, $products], fn(Product $p, ProductUnit $unit, ColumnExpand $c) => 
    Query::from($expand)
        ->innerJoin($p, $p->id->equals($c->productId))
        ->innerJoin($unit, $u->unit->equals($c->productUnit))
        ->where(case::bool()
            ->when($unit->form->equals('Solid'), $p->solid)
            ->when($unit->form->equals('Liquid'), $p->liquid)
            ->else(false))
        );

/*
Select q.*,
   p."Name",
   p."Solid",
   p."Liquid",
   s.form,
   case
       when s.form = 'Solid' then p."Solid"
       when s.form = 'Liquid' then p."Liquid"
       else false end as "Valid"
from (

SELECT pfr."Id"                   as "PlayFieldRevisionId",
    (elem -> 'Unit')::int            AS "Unit",
    (elem -> 'ProductId')::int AS "ProductId"
  FROM "PlayFieldRevisions" pfr,
       LATERAL jsonb_array_elements(pfr."Data" -> 'Columns') elem

  WHERE jsonb_typeof(pfr."Data" -> 'Columns') = 'array'
 ) q
     inner join "Products" p on p."Id" = q."ProductId"
     inner join (SELECT *
                 FROM (VALUES (0, 'Solid'),
                              (1, 'Solid'),
                              (2, 'Solid'),
                              (3, 'Solid'),
                              (4, 'Solid'),
                              (5, 'Solid'),
                              (6, 'Solid'),
                              (7, 'Solid'),
                              (8, 'Solid'),
                              (9, 'Solid'),
                              (10, 'Liquid'),
                              (11, 'Liquid'),
                              (12, 'Liquid'),
                              (13, 'Liquid'),
                              (14, 'Liquid'),
                              (15, 'Liquid'),
                              (16, 'Liquid'),
                              (17, 'Liquid'),
                              (18, 'Liquid'),
                              (19, 'Liquid'),
                              (20, 'Liquid'),
                              (25, 'Solid'),
                              (26, 'Liquid'),
                              (27, 'Liquid'),
                              (28, 'Solid'),
                              (29, 'Liquid'),
                              (30, 'Liquid'),
                              (31, 'Liquid'),
                              (32, 'Solid'),
                              (33, 'Solid'),
                              (34, 'Liquid'),
                              (35, 'Liquid'),
                              (36, 'Solid'),
                              (37, 'Solid'),
                              (38, 'Solid'),
                              (39, 'Solid'),
                              (40, 'Solid'),
                              (41, 'Solid'),
                              (42, 'Liquid'),
                              (43, 'Liquid'),
                              (44, 'Solid'),
                              (45, 'Solid')) AS t(productunit, form)) s on s.productunit = q."Unit"
where  not (case
            when s.form = 'Solid' then p."Solid"
            when s.form = 'Liquid' then p."Liquid"
            else false end)


*/