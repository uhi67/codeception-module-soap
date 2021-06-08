<?php

declare(strict_types=1);

use Codeception\Module\SOAP;
use Codeception\Util\Stub;
use Codeception\Util\Soap as SoapUtil;

/**
 * Class SoapTest
 * @group appveyor
 */
final class SoapTest extends \Codeception\PHPUnit\TestCase
{

    /**
     * @var \Codeception\Module\Soap
     */
    protected $module = null;

    protected $layout;

    public function _setUp()
    {
        $container = \Codeception\Util\Stub::make('Codeception\Lib\ModuleContainer');
        $frameworkModule = new \Codeception\Module\UniversalFramework($container);
        $frameworkModule->client = Stub::makeEmpty('\Codeception\Lib\Connector\Universal');
        $this->module = new \Codeception\Module\SOAP($container);
        $this->module->_setConfig(array(
            'schema' => 'http://www.w3.org/2001/xml.xsd',
            'endpoint' => 'http://codeception.com/api/wsdl'
        ));
        $this->module->_inject($frameworkModule);
        $this->layout = \Codeception\Configuration::dataDir().'/layout.xml';
        $this->module->isFunctional = true;
        $this->module->_before(Stub::makeEmpty('\Codeception\Test\Test'));
    }
    
    public function testXmlIsBuilt()
    {
        $dom = new \DOMDocument();
        $dom->load($this->layout);
        $this->assertXmlStringEqualsXmlString($dom->saveXML(), $this->module->xmlRequest->saveXML());
    }
    
    public function testBuildHeaders()
    {
        $this->module->haveSoapHeader('AuthHeader', ['username' => 'davert', 'password' => '123456']);
        $dom = new \DOMDocument();
        $dom->load($this->layout);
        $header = $dom->createElement('AuthHeader');
        $header->appendChild($dom->createElement('username', 'davert'));
        $header->appendChild($dom->createElement('password', '123456'));
        $dom->documentElement->getElementsByTagName('Header')->item(0)->appendChild($header);
        $this->assertXmlStringEqualsXmlString($dom->saveXML(), $this->module->xmlRequest->saveXML());
    }

    public function testBuildRequest()
    {
        $this->module->sendSoapRequest('KillHumans', "<item><id>1</id><subitem>2</subitem></item>");
        $this->assertNotNull($this->module->xmlRequest);
        $dom = new \DOMDocument();
        $dom->load($this->layout);
        $body = $dom->createElement('item');
        $body->appendChild($dom->createElement('id', '1'));
        $body->appendChild($dom->createElement('subitem', '2'));
        $request = $dom->createElementNS($this->module->_getConfig('schema'), 'ns:KillHumans');
        $request->appendChild($body);
        $dom->documentElement->getElementsByTagName('Body')->item(0)->appendChild($request);
        $this->assertXmlStringEqualsXmlString($dom->saveXML(), $this->module->xmlRequest->saveXML());
    }

    public function testBuildRequestWithDomNode()
    {
        $dom = new \DOMDocument();
        $dom->load($this->layout);
        $body = $dom->createElement('item');
        $body->appendChild($dom->createElement('id', '1'));
        $body->appendChild($dom->createElement('subitem', '2'));
        $request = $dom->createElementNS($this->module->_getConfig('schema'), 'ns:KillHumans');
        $request->appendChild($body);
        $dom->documentElement->getElementsByTagName('Body')->item(0)->appendChild($request);

        $this->module->sendSoapRequest('KillHumans', $body);
        $this->assertXmlStringEqualsXmlString($dom->saveXML(), $this->module->xmlRequest->saveXML());
    }
    
    public function testSeeXmlIncludes()
    {
        $dom = new DOMDocument();
        $this->module->xmlResponse = $dom;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML('<?xml version="1.0" encoding="UTF-8"?>    <doc> <a a2="2" a1="1" >123</a>  </doc>');
        $this->module->seeSoapResponseIncludes('<a    a2="2"      a1="1" >123</a>');
    }

    public function testSeeXmlContainsXPath()
    {
        $dom = new DOMDocument();
        $this->module->xmlResponse = $dom;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML('<?xml version="1.0" encoding="UTF-8"?>    <doc> <a a2="2" a1="1" >123</a>  </doc>');
        $this->module->seeSoapResponseContainsXPath('//doc/a[@a2=2 and @a1=1]');
    }

    public function testSeeXmlNotContainsXPath()
    {
        $dom = new DOMDocument();
        $this->module->xmlResponse = $dom;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML('<?xml version="1.0" encoding="UTF-8"?>    <doc> <a a2="2" a1="1" >123</a>  </doc>');
        $this->module->dontSeeSoapResponseContainsXPath('//doc/a[@a2=2 and @a31]');
    }


    public function testSeeXmlEquals()
    {
        $dom = new DOMDocument();
        $this->module->xmlResponse = $dom;
        $xml = '<?xml version="1.0" encoding="UTF-8"?> <doc> <a a2="2" a1="1" >123</a>  </doc>';
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);
        $this->module->seeSoapResponseEquals($xml);
    }

    public function testSeeXmlIncludesWithBuilder()
    {
        $dom = new DOMDocument();
        $this->module->xmlResponse = $dom;
        $dom->loadXML('<?xml version="1.0" encoding="UTF-8"?>'."\n".'  <doc><a    a2="2" a1="1"  >123</a></doc>');
        $xml = SoapUtil::request()->doc->a
                ->attr('a2', '2')
                ->attr('a1', '1')
                ->val('123');
        $this->module->seeSoapResponseIncludes($xml);
    }
    
    public function testGrabTextFrom()
    {
        $dom = new DOMDocument();
        $this->module->xmlResponse = $dom;
        $dom->loadXML('<?xml version="1.0" encoding="UTF-8"?><doc><node>123</node></doc>');
        $res = $this->module->grabTextContentFrom('doc node');
        $this->assertEquals('123', $res);
        $res = $this->module->grabTextContentFrom('descendant-or-self::doc/descendant::node');
        $this->assertEquals('123', $res);
    }

	/**
	 * @dataProvider provSoapEncode
	 * @param string $expected
	 * @param mixed $value
	 */
	public function testSoapEncode($expected, $value) {
		if(is_array($value) && isset($value[0]) && preg_match('~^{([^}]+)}$~', $value[0], $mm)) {
			array_shift($value);
			$className = $mm[1];
			$value = new $className(...$value);
		}
		$encoded = SOAP::soapEncode($value);
		$this->assertXmlStringEqualsXmlString($expected, $encoded);
	}
	function provSoapEncode() {
		return [
			[/** @lang */'<item xsi:type="xsd:int" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">13</item>',
				13],
			[/** @lang */'<item xsi:type="SOAP-ENC:Struct" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                            <a xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" SOAP-ENC:arrayType="xsd:int[3]" xsi:type="SOAP-ENC:Array">
                                <item xsi:type="xsd:int">13</item>
                                <item xsi:type="xsd:int">14</item>
                                <item xsi:type="xsd:int">15</item>
                            </a>
                        </item>',
				['a'=>[13, 14, 15]]],
			[/** @lang */'<item xsi:type="SOAP-ENC:Struct" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                            <a xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" SOAP-ENC:arrayType="xsd:anyType[3]" xsi:type="SOAP-ENC:Array">
                                <item xsi:type="xsd:int">13</item>
                                <item xsi:type="xsd:boolean">true</item>
                                <item xsi:type="xsd:string">foo</item>
                            </a>
                        </item>',
				['a'=>[13, true, 'foo']]],
			[/** @lang */'<item xsi:type="xsd:dateTime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">2004-04-12T13:20:00-05:00</item>', ['{\DateTime}',
				'2004-04-12T13:20:00-05:00']],
		];
	}

	/**
	 * @dataProvider provSendSoapRequestFromArray
	 * @param string $expected -- request envelope
	 * @param array $data -- array of structured arguments
	 *
	 * @throws \Codeception\Exception\ModuleRequireException
	 */
	public function testSendSoapRequestFromArray($expected, $method, $data) {
		$this->module->sendSoapRequest($method, $data);

		$request = $this->module->xmlRequest;
		$this->assertXmlStringEqualsXmlString($expected, $request->saveXML());
	}
	function provSendSoapRequestFromArray() {
		return [
			['<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
					<soapenv:Header/>
					<soapenv:Body>
						<ns:myOperation xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ns="http://www.w3.org/2001/xml.xsd">
							<a xsi:type="xsd:int">13</a>
							<b xsi:type="xsd:boolean">true</b>
							<c xsi:type="xsd:string">foo</c>
						</ns:myOperation>
					</soapenv:Body>
				</soapenv:Envelope>',
				'myOperation',
				['a'=>13, 'b'=>true, 'c'=>'foo']
			],
			['<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
					<soapenv:Header/>
					<soapenv:Body>
						<ns:myOperation xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:ns="http://www.w3.org/2001/xml.xsd">
							<a SOAP-ENC:arrayType="xsd:int[3]" xsi:type="SOAP-ENC:Array">
								<item xsi:type="xsd:int">1</item>
								<item xsi:type="xsd:int">2</item>
								<item xsi:type="xsd:int">3</item>
							</a>
							<b SOAP-ENC:arrayType="xsd:anyType[3]" xsi:type="SOAP-ENC:Array">
								<item xsi:type="xsd:string">foo</item>
								<item xsi:type="xsd:int">13</item>
								<item xsi:type="xsd:boolean">false</item>
							</b>
							<c xsi:type="xsd:string">baar</c>
						</ns:myOperation>
					</soapenv:Body>
				</soapenv:Envelope>',
				'myOperation',
				['a'=>[1,2,3], 'b'=>['foo', 13, false], 'c'=>'baar']
			],
			['<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
					<soapenv:Header/>
					<soapenv:Body>
						<ns:myOperation xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:ns="http://www.w3.org/2001/xml.xsd">
							<a SOAP-ENC:arrayType="xsd:int[3]" xsi:type="SOAP-ENC:Array">
								<item xsi:type="xsd:int">1</item>
								<item xsi:type="xsd:int">2</item>
								<item xsi:type="xsd:int">3</item>
							</a>
							<b SOAP-ENC:arrayType="xsd:anyType[3]" xsi:type="SOAP-ENC:Array">
								<item xsi:type="xsd:string">foo</item>
								<item xsi:type="xsd:int">13</item>
								<item xsi:type="xsd:boolean">false</item>
							</b>
							<c xsi:type="xsd:string">baar</c>
						</ns:myOperation>
					</soapenv:Body>
				</soapenv:Envelope>',
				'myOperation',
				['a'=>[1,2,3], 'b'=>['foo', 13, false], 'c'=>'baar']
			]
		];
	}

}
