<?php
namespace FrankyNet\FlexMenuBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_CLASS)]
class BelongsTo {

    protected ?string $route = null;

    public function __construct(string $route)
    {
        $this->route = $route;
    }

}