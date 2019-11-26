<?php

namespace MichielKempen\NovaOrderField;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\Http\Requests\NovaRequest;
use Spatie\EloquentSortable\Sortable;

trait Orderable
{
    /**
     * The user-defined OrderFieldAttribute
     *
     * @var string
     */
    public static $defaultOrderField;

    /**
     * Build an "index" query for the given resource.
     *
     * @param  NovaRequest  $request
     * @param  Builder  $query
     * @return Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        if(static::canQueryPivotOrder() && $pivot = static::orderedManyPivotModel($request)) {
            return static::orderedPivotIndexQuery($request, $query, $pivot);
        }

        if(!static::canQueryOrder()) {
            return $query;
        }

        return static::orderedIndexQuery(
            $query,
            static::orderByFieldAttribute($request)
        );
    }

    /**
     * Apply an orderBy ASC statement on the query for given attribute
     *
     * @param  Builder  $query
     * @param  string  $attribute
     * @return Builder
     */
    public static function orderedIndexQuery($query, $attribute)
    {
        if(!$attribute) {
            abort(500, static::$model . ' should implement the ' . Sortable::class . ' interface and define a valid order_column_name.');
        }

        $query->getQuery()->orders = [];

        return $query->orderBy($attribute);
    }

    /**
     * Find the orderByField for current request
     *
     * @param  NovaRequest  $request
     * @return string
     */
    public static function canQueryOrder()
    {
        return !is_null(static::modelOrderByFieldAttribute(static::newModel()));
    }

    /**
     * Find the orderByField for current request
     *
     * @param  NovaRequest  $request
     * @return string
     */
    public static function canQueryPivotOrder()
    {
        return method_exists(static::class, 'orderedManyPivotModel')
            && method_exists(static::class, 'orderedPivotIndexQuery');
    }

    /**
     * Find the orderByField for current request
     *
     * @param  NovaRequest  $request
     * @return string
     */
    public static function orderByFieldAttribute(NovaRequest $request)
    {
        return static::$defaultOrderField
            ?? static::modelOrderByFieldAttribute(static::newModel());
    }

    /**
     * Extract the order_column_name from a Sortable Model
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return null|string
     */
    public static function modelOrderByFieldAttribute($model)
    {
        if (!$model instanceof Sortable) {
            return;
        }

        return $model->sortable['order_column_name'] ?? null;
    }
}