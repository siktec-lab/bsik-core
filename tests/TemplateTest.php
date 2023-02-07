<?php

require_once "bootstrap.php";

use \PHPUnit\Framework\TestCase;
use \Siktec\Bsik\Render\Templates\Template;
use \Siktec\Bsik\Render\Templates\TemplatingExtension;

class TemplateTest extends TestCase
{
    
    private static ?Template $Template;

    public static function setUpBeforeClass() : void {
        self::$Template = new Template(
            cache_enable    : false,
            debug           : true
        );
        self::$Template->addFolders(
            paths : [dirname(__FILE__).DS."resources"]
        );
    }

    public static function tearDownAfterClass() : void {
        self::$Template = null;
    }

    public function setUp() : void {
        
    }
    public function tearDown() : void {

    }

    /**
     * Render template and assert equals
     *
     * @param string $expected
     * @param string $template_name
     * @param string $template
     * @param array  $data
     */
    protected function assertRenderNewTemplate($expected, $template_name, $template, array $context = [], string $message = "")
    {
        //Set the template:
        self::$Template->addTemplates([
            $template_name => $template
        ]);
        $this->assertRenderTemplate($expected, $template_name, $context, $message);
    }

    protected function assertRenderTemplate($expected, $template_name, array $context = [], string $message = "")
    {
        $result = self::$Template->render(
            name    : preg_replace('/.tpl$/', '', $template_name), 
            context : $context
        );
        $this->assertEquals($expected, (string)$result, $message);
    }

    //loads a dynamic template from string , names it and execute:
    public function testLoadedStringTemplateExecution()
    {
        $this->assertRenderNewTemplate(
            expected        : "SIKTEC",
            template_name   : "strong_name.tpl",
            template        : "{{ name|upper }}",
            context         :  [ "name" => "siktec"],
            message         : "Failed simple string template rendering"
        );
    }
    //loads a static (file) template, render test:
    public function testFileTemplateExecution()
    {
        $this->assertRenderTemplate(
            expected        : "SIKTEC",
            template_name   : "test_templates",
            context         : ["name" => "siktec"],
            message         : "Failed simple file template rendering"
        );
    }
    //Combine string and files tests with combination of those:
    public function testCombineIncludesTemplateExecution()
    {
        $this->assertRenderNewTemplate(
            expected        : "SIKTEC-PLAT BY SIKTEC",
            template_name   : "combine.tpl",
            template        : "{{ include('test_templates.tpl') }}-{{ last|upper }} BY {{ include('strong_name.tpl') }}",
            context         :  [ "name" => "siktec", "last" => "plat"],
            message         : "Failed combine string + file template rendering"
        );
    }


    public function testExtensionFilterArrayValues()
    {
        self::$Template->addExtension(new TemplatingExtension());
        $data = ['foo' => 1, 'bar' => 2, 'zoo' => 3];
        $this->assertRenderNewTemplate(
            expected        : '1-2-3',
            template_name   : "array_values_join.tpl",
            template        : '{{ data|array_values|join("-") }}',
            context         :  compact('data'),
            message         : "Failed extended array_values filter"
        );
    }

    public function testExtensionFilterArrayKeys()
    {
        self::$Template->addExtension(new TemplatingExtension());
        $data = ['foo' => 1, 'bar' => 2, 'zoo' => 3];
        $this->assertRenderNewTemplate(
            expected        : 'foo+bar+zoo',
            template_name   : "array_keys_join.tpl",
            template        : '{{ data|array_keys|join("+") }}',
            context         :  compact('data'),
            message         : "Failed extended array_keys filter"
        );
    }

    public function testExtensionFunctionArrayToAttrs()
    {
        self::$Template->addExtension(new TemplatingExtension());
        $data = ['href' => 'foo.html', 'class' => 'big small', 'checked' => true, 'disabled' => false];
        $this->assertRenderNewTemplate(
            expected        : 'href="foo.html" class="big small" checked="checked"',
            template_name   : "array_to_attrs_join.tpl",
            template        : '{{ render_as_attributes(data)|raw }}',
            context         :  compact('data'),
            message         : "Failed extended render_as_attributes function"
        );
    }


}