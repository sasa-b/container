<?php
/**
 * Created by PhpStorm.
 * User: sasablagojevic
 * Date: 10/2/17
 * Time: 4:16 PM
 */

namespace Foundation\Exceptions;


use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \InvalidArgumentException implements ContainerExceptionInterface
{

}