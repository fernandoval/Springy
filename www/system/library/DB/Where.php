<?php
/** \file
 *  Springy.
 *
 *  \brief      Child database class to construct WHERE clause.
 *  \copyright  Copyright (c) 2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.2
 *  \ingroup    framework
 */
namespace Springy\DB;

/**
 *  \brief Class to construct database WHERE clauses.
 */
class Where extends Conditions
{
    /**
     *  \brief Convert the objet to a string in database WHERE form.
     *
     *  The values of the parameter will be in question mark form and can be obtained with params() method.
     */
    public function __toString()
    {
        $where = parent::__toString();

        return (!empty($where) ? ' WHERE ' : '').$where;
    }
}
