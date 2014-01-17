<?php
/**
 * This file is part of the Postmark package.
 *
 * PHP version 5.4
 *
 * @category Library
 * @package  Postmark
 * @author   Arnas Lukosevicius <arnaslu@gmail.com>
 * @license  https://github.com/arnaslu/postmark/LICENSE.md MIT Licence
 * @link     https://github.com/arnaslu/postmark
 */

namespace Postmark\Exception;

/**
 * This exceptions is thrown when Postmark API returns HTTP status code 422 with
 * error details in JSON format
 *
 * @category Library
 * @package  Postmark
 * @author   Arnas Lukosevicius <arnaslu@gmail.com>
 * @license  https://github.com/arnaslu/postmark/LICENSE.md MIT Licence
 * @link     https://github.com/arnaslu/postmark
 */
class PostmarkErrorException extends \RuntimeException
{

}