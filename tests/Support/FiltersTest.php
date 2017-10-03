<?php

namespace Tests\Support;

use Ethereal\Support\Filters;
use Orchestra\Testbench\TestCase;

class FiltersTest extends TestCase
{
    /**
     * @test
     */
    public function it_filters_fields_with_prefix_and_returns_them_without_prefix()
    {
        self::assertEquals(['title' => 'title', 'name' => '1'], $this->getPassedFilters(Filters::byRules([
            'flt_title' => 'title',
            'flt_name' => '1',
            'last_name' => '2',
        ], [
            'title' => '',
            'name' => '',
            'last_name' => 'in:2,1',
        ], 'flt_')));
    }

    /**
     * @test
     */
    public function it_filters_orders_with_prefix_and_returns_them_without_prefix()
    {
        self::assertEquals(['title' => 'asc', 'last_name' => 'desc'], $this->getPassedFilters(Filters::orderable([
            'ord_title' => 'asc',
            'ord_name' => '1',
            'ord_last_name' => 'desc',
        ], ['title', 'name', 'last_name'], 'ord_')));
    }

    /**
     * @test
     */
    public function it_returns_all_non_empty_fields_with_rules_when_no_prefix_is_provided()
    {
        self::assertEquals(['name' => '1'], $this->getPassedFilters(Filters::byRules([
            'title' => '',
            'name' => '1',
            'last_name' => '2',
        ], [
            'title' => '',
            'name' => '',
        ])));
    }

    /**
     * @test
     */
    public function it_returns_only_values_that_pass_validation_rules()
    {
        self::assertEquals(['last_name' => '2'], $this->getPassedFilters(Filters::byRules([
            'title' => '',
            'name' => '1',
            'last_name' => '2',
        ], [
            'title' => 'required',
            'name' => 'in:2,3',
            'last_name' => 'in:2,1',
        ])));
    }

    /**
     * @test
     */
    public function it_returns_values_that_are_valid_for_sorting()
    {
        self::assertEquals(['title' => 'asc'], $this->getPassedFilters(Filters::orderable([
            'title' => 'asc',
            'name' => '1',
            'last_name' => '2',
        ], ['title', 'name'])));
    }

    protected function getPassedFilters($generator)
    {
        $passed = [];

        foreach ($generator as $key => $value) {
            $passed[$key] = $value;
        }

        return $passed;
    }
}
