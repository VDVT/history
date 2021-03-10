<?php

namespace VDVT\History\Traits;

trait IgnoreAttributeTrait
{
    /**
     * Only write log columns
     *
     * @var array
     */
    protected $historyOnlySpecialColumns = array();

    /**
     * Exclude columns
     *
     * @var array
     */
    protected $ignoreLogAttributes = array('updated_at', 'updated_by');

    /**
     * @param  mixed $fieldsChanged
     * @return array
     */
    public function ignoreAttributes(array $fieldsChanged): array
    {
        if (!empty($this->historyOnlySpecialColumns) && is_array($this->historyOnlySpecialColumns)) {
            return array_intersect_key(
                $fieldsChanged, /* main array*/
                array_flip( /* to be extracted */
                    $this->historyOnlySpecialColumns
                )
            );
        }

        if (is_array($this->ignoreLogAttributes)) {
            return array_diff_key($fieldsChanged, array_flip($this->ignoreLogAttributes));
        }

        return $fieldsChanged;
    }
}
