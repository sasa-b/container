<?php
/**
 * Created by PhpStorm.
 * User: sasablagojevic
 * Date: 10/2/17
 * Time: 4:17 PM
 */

namespace Foundation\Container\Exceptions;


use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \InvalidArgumentException implements NotFoundExceptionInterface
{

}