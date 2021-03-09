<?php
namespace VDVT\History\Supports;

trait HistoryFormatJsonTrait
{
    /**
     * [$relationShipAttributes description]
     * @var [type]
     */
    protected $jsonAttributes = [
        //      'column_name' => [
        // 'attribute_title'         => 'Product',
        // 'attributes_primary'      => 'id',
        // 'attribute_display'       => 'name',
        // 'attribute_display_title' => 'Name',
        // 'attributes_log'          => ['price', 'name', 'qty']
        //      ],
    ];

    /**
     * [formatBoolean description]
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    protected function formatJsonAttribute($attribute, $origin, $current, $sessionPath)
    {
        $origin = $this->formatJsonArray($origin ?: []);
        $current = $this->formatJsonArray($current ?: []);

        foreach (array_diff($current, $origin) as $attr) {
            # log for added attribute json
            $formatted = $this->getInformationAttributeJson($attribute, $attr, $sessionPath);
            $this->saveLogAttribute($formatted);
        }

        foreach (array_diff($origin, $current) as $attr) {
            # log for removed attribute json
            $formatted = $this->getInformationAttributeJson($attribute, $attr, $sessionPath, false);
            $this->saveLogAttribute($formatted);
        }
    }

    /**
     * [getInformationAttributeJson description]
     * @param  [type] $attribute [description]
     * @return [type]            [description]
     */
    protected function getInformationAttributeJson($attribute, $value, $sessionPath, $isCreated = true)
    {
        $fieldName = $this->getHistoryDisplayAttribute($attribute);
        $tableName = $this->getHistoryDisplayTable();

        return [
            'content' => __('vdvt.history.actions.' . ($isCreated ? 'created_with_value' : 'deleted_with_value'), [
                'name' => $fieldName,
                'value' => $value,
            ]),
            'value_current' => $value,
            'field_name' => $fieldName,
            'path_session' => $sessionPath,
            'table_name' => $tableName,
            'attribute_name' => $attribute,
        ];
    }

    /**
     * Reset Array Key
     *
     * @param  array  $items
     * @return array
     */
    protected function formatJsonArray(array $items = []): array
    {
        return array_values($items);
    }
}
