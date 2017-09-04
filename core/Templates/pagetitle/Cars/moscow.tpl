{extends 'pagetitle/Cars/common.tpl'}
{block 'common'}Купить {$name} для автомобилей {$mark_name}{if $model_name?} {$year_name ?: $model_name}{/if} в Москве. Доставка по России.{/block}