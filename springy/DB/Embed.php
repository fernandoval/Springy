<?php
/** @file
 *  Springy.
 *
 *  @brief      Child database class to construct embbeded objects.
 *
 *  @note       This class is experimental and not is terminated yet.
 *
 *  @copyright  Copyright (c) 2016 Fernando Val
 *  @author     Fernando Val - fernando.val@gmail.com
 *
 *  @version    0.1.0.1
 *  @ingroup    framework
 */
class Embed
{
    /// The attribute name
    protected $name = '';
    /// The embed type
    protected $type = '';
    /// The name of the class of the embedde object
    protected $model = null;
    /// The name of the column in embedded object used to find the rows
    protected $foundBy = null;
    /// The name of the column in incorporator model used to find the rows
    protected $column = null;
    /// The name of the wanted columns of the embedded object
    protected $columns = null;
    /// The order of the rows
    protected $order = [];
    /// The start offset of rows in embedded result set
    protected $offset = 0;
    /// The limit of offset rows in embedded result set
    protected $limit = 0;
    /// An array of embedded objects in this embedded object
    protected $embedded = [];

    const TYPE_DATA = 'data';
    const TYPE_LIST = 'list';

    /**
     *  @brief The constructor method.
     */
    public function __construct($name, $type = self::TYPE_LIST, $model = null, $foundBy = null, $column = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->model = $model;
        $this->foundBy = $foundBy;
        $this->column = $column;
        if ($type == self::TYPE_DATA) {
            $limit = 1;
        }
    }

    /**
     *  @brief Add an embedded object in this embedded object.
     *
     *  @params $embedded must be an Embed object.
     */
    public function addEmbedded(self $embedded)
    {
        $this->embedded[] = clone $embedded;
    }

    /**
     *  @brief Set the name of the column in incorporator model used to find embbeded object rows.
     */
    public function column(string $column)
    {
        $this->column = $column;
    }

    /**
     *  @brief Set the wanted embbed object columns.
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     *  @brief Set the column key in embbeded object used to find rows.
     */
    public function foundBy(string $column)
    {
        $this->foundBy = $column;
    }

    /**
     *  @brief Set the offset and limit of rows.
     */
    public function limit(int $limit, int $offset = 0)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     *  @brief Set the embbeded model object name.
     */
    public function model($model)
    {
        $this->model = $model;
    }

    /**
     *  @brief The attribute name of the embedded object.
     *
     *  @params string $name if defined change the attribute name.
     *
     *  @return A string with the attribute name.
     */
    public function name($name = null)
    {
        if (!is_null($name)) {
            $this->name = $name;
        }

        return $this->name;
    }

    /**
     *  @brief Set the order of the rows.
     */
    public function order(array $order)
    {
        $this->order = $order;
    }

    /**
     *  @brief Parse this object and return an array to use in Model objects.
     */
    public function get()
    {
        $embed = [
            'type'     => $this->type,
            'model'    => $this->model,
            'columns'  => $this->columns,
            'found_by' => $this->foundBy,
            'column'   => $this->column,
            'order'    => $this->order,
            'offset'   => $this->offset,
            'limit'    => $this->limit,
        ];

        $childs = [];
        foreach ($this->embedded as $child) {
            $childs[] = $child->get();
        }
        if (count($childs)) {
            $embed['embedded_obj'] = $childs;
        }

        return [$this->name => $embed];
    }
}
