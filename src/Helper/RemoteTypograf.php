<?php namespace Skvn\Crud\Helper;

class RemoteTypograf
{
    protected $_entityType = 4;
    protected $_useBr = 1;
    protected $_useP = 1;
    protected $_maxNobr = 3;
    protected $_encoding = 'UTF-8';
    protected $_quotA = 'laquo raquo';
    protected $_quotB = 'bdquo ldquo';

    function __construct ($encoding = null)
    {
        if ($encoding) $this->_encoding = $encoding;
    }

    function htmlEntities()
    {
        $this->_entityType = 1;
    }

    function xmlEntities()
    {
        $this->_entityType = 2;
    }

    function mixedEntities()
    {
        $this->_entityType = 4;
    }

    function noEntities()
    {
        $this->_entityType = 3;
    }

    function br ($value)
    {
        $this->_useBr = $value ? 1 : 0;
    }

    function p ($value)
    {
        $this->_useP = $value ? 1 : 0;
    }

    function nobr ($value)
    {
        $this->_maxNobr = $value ? $value : 0;
    }

    function quotA ($value)
    {
        $this->_quotA = $value;
    }

    function quotB ($value)
    {
        $this->_quotB = $value;
    }

    function processText ($text)
    {
        $text = str_replace ('&', '&amp;', $text);
        $text = str_replace ('<', '&lt;', $text);
        $text = str_replace ('>', '&gt;', $text);

        $SOAPBody = '<?xml version="1.0" encoding="' . $this->_encoding . '"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
	<ProcessText xmlns="http://typograf.artlebedev.ru/webservices/">
	  <text>' . $text . '</text>
      <entityType>' . $this->_entityType . '</entityType>
      <useBr>' . $this->_useBr . '</useBr>
      <useP>' . $this->_useP . '</useP>
      <maxNobr>' . $this->_maxNobr . '</maxNobr>
      <quotA>' . $this->_quotA . '</quotA>
      <quotB>' . $this->_quotB . '</quotB>
	</ProcessText>
  </soap:Body>
</soap:Envelope>';

        $host = 'typograf.artlebedev.ru';
        $SOAPRequest = 'POST /webservices/typograf.asmx HTTP/1.1
Host: typograf.artlebedev.ru
Content-Type: text/xml
Content-Length: ' . strlen ($SOAPBody). '
SOAPAction: "http://typograf.artlebedev.ru/webservices/ProcessText"

'.
            $SOAPBody;

        $remoteTypograf = fsockopen ($host, 80);
        fwrite ($remoteTypograf, $SOAPRequest);
        $typografResponse = '';
        while (!feof ($remoteTypograf))
        {
            $typografResponse .= fread ($remoteTypograf, 8192);
        }
        fclose ($remoteTypograf);

        $startsAt = strpos ($typografResponse, '<ProcessTextResult>') + 19;
        $endsAt = strpos ($typografResponse, '</ProcessTextResult>');
        $typografResponse = substr ($typografResponse, $startsAt, $endsAt - $startsAt - 1);

        $typografResponse = str_replace ('&amp;', '&', $typografResponse);
        $typografResponse = str_replace ('&lt;', '<', $typografResponse);
        $typografResponse = str_replace ('&gt;', '>', $typografResponse);

        return  $typografResponse;
    }
}

