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

namespace Postmark;

use JsonSerializable;

/**
 * Message object for Postmark API wrapper. Implements JsonSerializable, so when
 * passed through json_encode() function it will use it's jsonSerialize() function
 * to return relevant data.
 *
 * @category Library
 * @package  Postmark
 * @author   Arnas Lukosevicius <arnaslu@gmail.com>
 * @author   John Piasetzki <john@piasetzki.name>
 * @license  https://github.com/arnaslu/postmark/LICENSE.md MIT Licence
 * @link     https://github.com/arnaslu/postmark
 */
class Message implements JsonSerializable
{
    private $_from;
    private $_to;
    private $_cc;
    private $_bcc;
    private $_subject;
    private $_tag;
    private $_htmlBody;
    private $_textBody;
    private $_replyTo;
    private $_headers = array();
    private $_attachments = array();

    /**
     * Adds "To" address
     *
     * @param string $address Receiver's email address
     * @param string $name    Receiver's name
     *
     * @return void
     */
    public function addTo($address, $name = null)
    {
        $this->_addAddress('to', $address, $name);
    }

    /**
     * Adds "CC" address
     *
     * @param string $address Receiver's email address
     * @param string $name    Receiver's name
     *
     * @return void
     */
    public function addCc($address, $name = null)
    {
        $this->_addAddress('cc', $address, $name);
    }

    /**
     * Adds "BCC" address
     *
     * @param string $address Receiver's email address
     * @param string $name    Receiver's name
     *
     * @return void
     */
    public function addBcc($address, $name = null)
    {
        $this->_addAddress('bcc', $address, $name);
    }

    /**
     * Sets "Reply-To" address
     *
     * @param string $address Sender's email address
     * @param string $name    Sender's name
     *
     * @return void
     */
    public function setReplyTo($address, $name = null)
    {
        $this->_replyTo = $this->_createAddress($address, $name);
    }

    /**
     * Sets "From" address
     *
     * @param string $address Sender's email address
     * @param string $name    Sender's name
     *
     * @return void
     */
    public function setFrom($address, $name = null)
    {
        $this->_from = $this->_createAddress($address, $name);
    }

    /**
     * Adds formatted email address (with optional name) in RFC 5322 format
     *
     * @param string $type    Address type ([to,cc,bcc])
     * @param string $address Receiver's email address
     * @param string $name    Receiver's name
     *
     * @return void
     */
    private function _addAddress($type, $address, $name = null)
    {
        $type = '_'.$type;
        if (!empty($this->$type)) {
            $this->$type .= ', ';
        }
        $this->$type .= $this->_createAddress($address, $name);
    }

    /**
     * Sets message subject
     *
     * @param string $subject Email message subject line
     *
     * @return void
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;
    }

    /**
     * Sets message tag (i.e. "registration", "order", etc.) Can be used for
     * statistics.
     *
     * @param string $tag Email message tag
     *
     * @return void
     */
    public function setTag($tag)
    {
        $this->_tag = $tag;
    }

    /**
     * Sets message body in HTML format. Does not generate HTML itself, must receive
     * already formatted HTML message as parameter.
     *
     * @param string $message Email body in HTML
     *
     * @return void
     */
    public function setHtmlBody($message)
    {
        $this->_htmlBody = $message;
    }

    /**
     * Sets message body in plain text format.
     *
     * @param string $message Email body in plain text
     *
     * @return void
     */
    public function setTextBody($message)
    {
        $this->_textBody = $message;
    }

    /**
     * Adds file as message attachment.
     *
     * White-listed file extensions can be found in Postmark API documentation
     *
     * @param string $name        File's name (will be displayed to recipient)
     * @param mixed  $content     File's contents
     * @param string $contentType File's MIME content type (i.e. image/jpeg)
     *
     * @link http://developer.postmarkapp.com/developer-build.html#attachments
     *
     * @return void
     */
    public function addAttachment($name, $content, $contentType)
    {
        $this->_attachments[] = [
            'Name' => $name,
            'Content' => base64_encode($content),
            'ContentType' => $contentType
        ];
    }

    /**
     * Adds custom header
     *
     * @param string $name  Header name
     * @param string $value Header value
     *
     * @return void
     */
    public function addHeader($name, $value)
    {
        $this->_headers[] = array('Name' => $name, 'Value' => $value);
    }

    /**
     * Creates email address in RFC 5322 format
     * for example "John Smith <sender@example.com>"
     *
     * @param string $address Email part of the address
     * @param string $name    Name part of the address
     *
     * @return string
     */
    private function _createAddress($address, $name = null)
    {
        if (!is_null($name)) {
            if (1 === preg_match('/^[A-z0-9 ]*$/', $name)) {
                return str_replace('"', '', $name) . ' <' . $address . '>';
            }

            return '"' . str_replace('"', '', $name) . '" <' . $address . '>';
        }

        return $address;
    }

    /**
     * Specifies which data should be serialized when object is serialized to JSON
     *
     * @link http://www.php.net/manual/en/class.jsonserializable.php
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [
            'From' => $this->_from,
            'To' => $this->_to,
            'Cc' => $this->_cc,
            'Bcc' => $this->_bcc,
            'Subject' => $this->_subject,
            'Tag' => $this->_tag,
            'HtmlBody' => $this->_htmlBody,
            'TextBody' => $this->_textBody,
            'ReplyTo' => $this->_replyTo,
            'Headers' => $this->_headers,
            'Attachments' => $this->_attachments,
        ];

        return array_filter($json);
    }
}
