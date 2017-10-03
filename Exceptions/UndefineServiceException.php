<?php
/**
 * Created by PhpStorm.
 * User: sasablagojevic
 * Date: 10/2/17
 * Time: 4:17 PM
 */

namespace App\src\Container\Exceptions;


use Psr\Container\NotFoundExceptionInterface;

class UndefineServiceException extends \InvalidArgumentException implements NotFoundExceptionInterface
{

}