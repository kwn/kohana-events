<?php
 
class KohanaEventsTest extends Kohana_UnitTest_TestCase
{
    public $test_data = array();
    public $test_counts = array();

    //test class instantiation
    public function testInit() 
    {

   
    }
    
    // Test event register without arguments
    public function testRegister()
    {
        $TestEventInstance = Event::factory();
        $args = NULL;
        $event_id = Event::register('TestEvent','KohanaEventsTestFunction',$args,NULL,$TestEventInstance);
        $this->assertSame(Event::events('TestEvent',$TestEventInstance),array($event_id=> array('callback'=> 'KohanaEventsTestFunction', 'args'=> NULL, 'options'=> NULL)),'Testing Event::register without args');
    }

    // Test event register with arguments
    public function testRegisterArgs()
    {
        $TestEventInstance = Event::factory();
        $args = array('arg1','arg2');
        $event_id = Event::register('TestEvent','KohanaEventsTestFunction',$args,NULL,$TestEventInstance);
        $this->assertSame(Event::events('TestEvent',$TestEventInstance),array($event_id=> array('callback'=> 'KohanaEventsTestFunction', 'args'=> $args, 'options'=> NULL)),'Testing Event::register with args');
    }

    // Test event removal
    public function testRemove()
    {
        $TestEventInstance = Event::factory();
        // Add the event
        $event_id = Event::register('TestEvent','KohanaEventsTestFunction',$args,NULL,$TestEventInstance);

        // Remove it
        Event::remove('TestEvent',$event_id,$TestEventInstance);

        // Ensure it was removed
        $this->assertSame(Event::events('TestEvent',$TestEventInstance),array(),'Testing Event::remove');
    }    
    
    // Test event trigger function
    public function testTriggerFunction()
    {
        $TestEventInstance = Event::factory();

        // Test event with arguments on register
        $args = array('test_instance'=>$this);
        $event_id = Event::register('TestEvent','KohanaEventsTestFunction',$args,NULL,$TestEventInstance);

        // reset the test data properties
        $this->test_data = array();
        $this->test_counts = array();

        $arg = NULL;
        Event::trigger('TestEvent', $arg, $TestEventInstance);


        $this->assertSame($this->test_data['KohanaEventsTestFunction'],TRUE,'Testing Event::trigger with a function run');
        $this->assertSame($this->test_counts['KohanaEventsTestFunction'],1,'Testing Event::trigger with a function count');

        // clear the test data properties
        $this->test_data = array();
        $this->test_counts = array();


    }

    // Test event trigger function by passing data by reference with arguments set on "register"
    public function testTriggerFunctionReference()
    {

        $TestEventInstance = Event::factory();

        // Test event pass by reference with arguments on register
        $args = array('data'=> array(), 'counts' => array());
        $event_id = Event::register('TestEvent','KohanaEventsTestFunction',$args,NULL,$TestEventInstance);

        $arg = NULL;
        Event::trigger('TestEvent', $arg, $TestEventInstance);

        $this->assertSame($args['data']['KohanaEventsTestFunction'],TRUE,'Testing Event::trigger with a function run with reference');
        $this->assertSame($args['counts']['KohanaEventsTestFunction'],1,'Testing Event::trigger with a function count with reference');

    }
       
    // Test event call object/method
    public function testTriggerObjectReference()
    {

        $TestEventInstance = Event::factory();

        // Test event pass by reference with arguments on register
        $args = array('data'=> array(), 'counts' => array());
        $O = new KohanaEventsTestClass();
        $event_id = Event::register('TestEvent',array($O,'KohanaEventsTestObjectMethod'),$args,NULL,$TestEventInstance);

        $arg = NULL;
        Event::trigger('TestEvent', $arg, $TestEventInstance);

        $this->assertSame($args['data']['KohanaEventsTestClass.KohanaEventsTestObjectMethod'],TRUE,'Testing Event::trigger with an Object method run with reference');
        $this->assertSame($args['counts']['KohanaEventsTestClass.KohanaEventsTestObjectMethod'],1,'Testing Event::trigger with an Object method count with reference');

    }    

    // Test event call class/method
    public function testTriggerClassReference()
    {

        $TestEventInstance = Event::factory();

        // Test event pass by reference with arguments on register
        $args = array('data'=> array(), 'counts' => array());
        $event_id = Event::register('TestEvent',array('KohanaEventsTestClass','KohanaEventsTestStaticMethod'),$args,NULL,$TestEventInstance);

        $arg = NULL;
        Event::trigger('TestEvent', $arg, $TestEventInstance);

        $this->assertSame($args['data']['KohanaEventsTestClass.KohanaEventsTestStaticMethod'],TRUE,'Testing Event::trigger with a class method run with reference');
        $this->assertSame($args['counts']['KohanaEventsTestClass.KohanaEventsTestStaticMethod'],1,'Testing Event::trigger with a class method count with reference');

    }   

    // Test event call closure
    public function testTriggerClosureReference()
    {

        $C = function(&$args = array()) {
  
            $data = array();
            $counts = array();
            
            if (isset($args['test_instance']))
            {
                $data = &$args['test_instance']->test_data;
                $counts = &$args['test_instance']->test_counts;
            } 
            else
            {
                if (isset($args['data']))
                {
                    $data = &$args['data'];
                }

                if (isset($args['counts']))
                {
                    $counts = &$args['counts'];
                }   

            }

            $key = 'KohanaEventsTestClosure';
            $data[$key] = TRUE;
            if (!isset($counts[$key])) 
            {
                $counts[$key] = 0;
            }
            $counts[$key]++;    
        };

        $TestEventInstance = Event::factory();

        // Test event pass by reference with arguments on register
        $args = array('data'=> array(), 'counts' => array());
        $event_id = Event::register('TestEvent',$C,$args,NULL,$TestEventInstance);

        $arg = NULL;
        Event::trigger('TestEvent', $arg, $TestEventInstance);

        $this->assertSame($args['data']['KohanaEventsTestClosure'],TRUE,'Testing Event::trigger with a closure run with reference');
        $this->assertSame($args['counts']['KohanaEventsTestClosure'],1,'Testing Event::trigger with a closure count with reference');

    }   

    // Test args required
    public function testArgsRequired()
    {
        $args = array('var1'=> 'value1');
        $def = array(
            'var1' => array(
                'required'=> TRUE,
            )
        );

        // This should not throw an exception
        Args::check($args,$def);

        $Exception_Thrown = FALSE;
        try {
            $args = array('var2'=> 'value1');
            $def = array(
                'var1' => array(
                    'required'=> TRUE,
                )
            );

            // This should throw an exception
            Args::check($args,$def);
        }
        catch(Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        // Ensure the exception was thrown
        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check with a "required" argument');

    }

    // Test args default value
    public function testArgsDefault()
    {
        $args = array();
        $def = array(
            'var1' => array(
                'default'=> 'VALUE',
            )
        );

        Args::check($args,$def);

        $this->assertSame($args,array('var1'=> 'VALUE'),'Testing Args::check with a "default" value set');

    }


    // Test args default value function callback
    public function testArgsDefaultFunctionCallback()
    {

        // With no args
        $args = array();
        $def = array(
            'var1' => array(
                'getdefault'=> 'KohanaEventsDefaultCallback',
            )
        );

        Args::check($args,$def);

        $this->assertSame($args,array('var1'=> 'VALUE'),'Testing Args::check with a "getdefault" function callback');

        // with args
        $args = array();
        $callback_args = array();
        $def = array(
            'var1' => array(
                'getdefault'=> array('callback'=> 'KohanaEventsDefaultCallback', 'args'=> $callback_args),
            )
        );

        Args::check($args,$def);

        $this->assertSame($args,array('var1'=> 'VALUE'),'Testing Args::check with a "getdefault" function callback with arguments');

    }

    // Test args default value object.method callback
    public function testArgsDefaultObjectCallback()
    {

        // With no args
        $args = array();
        $O = new KohanaEventsTestClass();
        $def = array(
            'var1' => array(
                'getdefault'=> array($O,'KohanaEventsDefaultObjectMethod'),
            )
        );

        Args::check($args,$def);

        $this->assertSame($args,array('var1'=> 'VALUE'),'Testing Args::check with a "getdefault" object method callback');

        // with args
        $args = array();
        $callback_args = array();
        $O = new KohanaEventsTestClass();
        $def = array(
            'var1' => array(
                'getdefault'=> array('callback'=> array($O,'KohanaEventsDefaultObjectMethod'), 'args'=> $callback_args),
            )
        );

        Args::check($args,$def);

        $this->assertSame($args,array('var1'=> 'VALUE'),'Testing Args::check with a "getdefault" object method callback with arguments');

    }


    // Test args default value class.method callback
    public function testArgsDefaultClassCallback()
    {

        // With no args
        $args = array();
        $def = array(
            'var1' => array(
                'getdefault'=> array('KohanaEventsTestClass','KohanaEventsDefaultClassMethod'),
            )
        );

        Args::check($args,$def);

        $this->assertSame($args,array('var1'=> 'VALUE'),'Testing Args::check with a "getdefault" class method callback');

        // with args
        $args = array();
        $callback_args = array();
        $def = array(
            'var1' => array(
                'getdefault'=> array('callback'=> array('KohanaEventsTestClass','KohanaEventsDefaultClassMethod'), 'args'=> $callback_args),
            )
        );

        Args::check($args,$def);

        $this->assertSame($args,array('var1'=> 'VALUE'),'Testing Args::check with a "getdefault" class method callback with arguments');

    }

    // Test args default value closure callback
    public function testArgsDefaultClosureCallback()
    {

        $C = function(&$args = array())
        {
            return 'VALUE';
        };

        // With no args
        $args = array();
        $def = array(
            'var1' => array(
                'getdefault'=> $C,
            )
        );

        Args::check($args,$def);

        $this->assertSame($args,array('var1'=> 'VALUE'),'Testing Args::check with a "getdefault" closure callback');

        // with args
        $args = array();
        $callback_args = array();
        $def = array(
            'var1' => array(
                'getdefault'=> array('callback'=> $C, 'args'=> $callback_args),
            )
        );

        Args::check($args,$def);

        $this->assertSame($args,array('var1'=> 'VALUE'),'Testing Args::check with a "getdefault" closure callback with arguments');

    }


    // Test args casting true as default
    public function testArgsCasting()
    {

        $args = array(
            'var1'=> '12345'
        );
        $def = array(
            'var1' => array(
                'type'=> 'int',
            )
        );

        Args::check($args,$def);

        $this->assertSame($args['var1'],12345,'Testing Args::check type casting expected TRUE by default');
    }

    // Test args casting false
    public function testArgsCastingFalse()
    {

        $args = array(
            'var1'=> '12345'
        );
        $def = array(
            'var1' => array(
                'type'=> 'int',
            )
        );

        Args::check($args,$def,FALSE);

        $this->assertSame($args['var1'],'12345','Testing Args::check type casting FALSE');
    }


    // Test args type casting for all types
    public function testArgsTypeInt()
    {    

        // Int OK
        $args = array(
            'var1'=> 12345
        );
        $def = array(
            'var1' => array(
                'type'=> 'int',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type int is valid');

        // Int Not OK
        $args = array(
            'var1'=> 'abcde'
        );
        $def = array(
            'var1' => array(
                'type'=> 'int',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type int is invalid');

    }

    public function testArgsTypeFloat()
    {    

        // OK
        $args = array(
            'var1'=> 1.23045
        );
        $def = array(
            'var1' => array(
                'type'=> 'float',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type float is valid');

        // Not OK
        $args = array(
            'var1'=> 'abcde'
        );
        $def = array(
            'var1' => array(
                'type'=> 'float',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type float is invalid');

    }


    public function testArgsTypeBool()
    {    

        // send as bool
        $args = array(
            'var1'=> TRUE
        );
        $def = array(
            'var1' => array(
                'type'=> 'bool',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type bool is valid');

        // send as string

        $args = array(
            'var1'=> 'true'
        );
        $def = array(
            'var1' => array(
                'type'=> 'bool',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type bool sent as string is valid');

        // send as int

        $args = array(
            'var1'=> 1
        );
        $def = array(
            'var1' => array(
                'type'=> 'bool',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type bool sent as int is valid');


        // Not OK
        $args = array(
            'var1'=> 'abcde'
        );
        $def = array(
            'var1' => array(
                'type'=> 'bool',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type bool is invalid');

    }

    public function testArgsTypeString()
    {    

        // OK
        $args = array(
            'var1'=> 'string'
        );
        $def = array(
            'var1' => array(
                'type'=> 'string',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type string is valid');

        // Not OK
        $args = array(
            'var1'=> array()
        );
        $def = array(
            'var1' => array(
                'type'=> 'string',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type string is invalid');

    }


    public function testArgsTypeDatetimestr()
    {    

        // OK
        $args = array(
            'var1'=> '2008-08-07 18:11:31'
        );
        $def = array(
            'var1' => array(
                'type'=> 'datetimestr',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type datetimestr is valid');

        // Not OK
        $args = array(
            'var1'=> 'this is not a date string'
        );
        $def = array(
            'var1' => array(
                'type'=> 'datetimestr',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type datetimestr is invalid');

    }


    public function testArgsTypeDatetime()
    {    

        // OK
        $args = array(
            'var1'=> new DateTime()
        );
        $def = array(
            'var1' => array(
                'type'=> 'datetime',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type datetime is valid');

        // Not OK
        $args = array(
            'var1'=> 'this is not a datetime object'
        );
        $def = array(
            'var1' => array(
                'type'=> 'datetime',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type datetime is invalid');

    }


    public function testArgsTypeTimestamp()
    {    

        // OK
        $args = array(
            'var1'=> time()
        );
        $def = array(
            'var1' => array(
                'type'=> 'timestamp',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type timestamp is valid');

        // Not OK
        $args = array(
            'var1'=> 'this is not a timestamp'
        );
        $def = array(
            'var1' => array(
                'type'=> 'timestamp',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type timestamp is invalid');

    }

    public function testArgsTypeInvalues()
    {    

        // OK
        $args = array(
            'var1'=> 'valid1'
        );
        $def = array(
            'var1' => array(
                'type'=> 'invalues',
                'values' => array('valid1','valid2')
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type invalues is valid');

        // Not OK
        $args = array(
            'var1'=> 'notvalid'
        );
        $def = array(
            'var1' => array(
                'type'=> 'invalues',
                'values' => array('valid1','valid2')
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type invalues is invalid');

    }

    public function testArgsTypeArray()
    {    

        // OK
        $args = array(
            'var1'=> array()
        );
        $def = array(
            'var1' => array(
                'type'=> 'array',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type array is valid');

        // Not OK
        $args = array(
            'var1'=> 'notvalid'
        );
        $def = array(
            'var1' => array(
                'type'=> 'invalues',
                'values' => array('valid1','valid2')
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type array is invalid');

    }

    public function testArgsTypeObject()
    {    
        $O = new KohanaEventsTestClass();
        // OK
        $args = array(
            'var1'=> $O
        );
        $def = array(
            'var1' => array(
                'type'=> 'object',
                'class'=> 'KohanaEventsTestClass'
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type object is valid');

        // Not OK
        $args = array(
            'var1'=> 'notvalid'
        );
        $def = array(
            'var1' => array(
                'type'=> 'object',
                'class'=> 'KohanaEventsTestClass'
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type object is invalid');

    }

    public function testArgsTypeNull()
    {    
        $O = new KohanaEventsTestClass();
        // OK
        $args = array(
            'var1'=> NULL
        );
        $def = array(
            'var1' => array(
                'type'=> array('null','string'),

            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check type null is valid');

        // Not OK
        $args = array(
            'var1'=> 'string'
        );
        $def = array(
            'var1' => array(
                'type'=> 'null',
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check type null is invalid');

    }


    public function testArgsTypes()
    {    

        $Exception_Thrown = FALSE;

        try {
            $def = array(
                'var1' => array(
                    'type'=> array('int','string','array'),

                )
            );            

            $args = array(
                'var1'=> 1
            );            
            Args::check($args,$def);

            $args = array(
                'var1'=> 'string'
            );            
            Args::check($args,$def);       

             $args = array(
                'var1'=> array('value')
            );            
            Args::check($args,$def);    
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
            
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check multiple types are allowed');


    }



    public function testArgsValidation()
    {    

        // test validation in args::check with function
        $args = array(
            'var1'=> 'testvalue'
        );
        $def = array(
            'var1' => array(
                'type'=> 'string',
                'validate'=> array(
                    'is_string',
                )
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check validation with function is valid');

        // Test validation with a callback
        $args = array(
            'var1'=> 'testvalue'
        );
        $def = array(
            'var1' => array(
                'type'=> 'string',
                'validate'=> array(
                    array(array('Valid','alpha')),
                )
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,FALSE,'Testing Args::check validation with class/method is valid');


        // ensure Validate throws error
        $args = array(
            'var1'=> 'notvalid'
        );
        $def = array(
            'var1' => array(
                'type'=> 'string',
                'validate'=> array(
                    array(array('Valid','digit'))
                )
            )
        );

        $Exception_Thrown = FALSE;

        try {
            Args::check($args,$def);
        }
        catch (Exception $E)
        {
            $Exception_Thrown = TRUE;
        }

        $this->assertSame($Exception_Thrown,TRUE,'Testing Args::check validation is invalid');

    }


}



class KohanaEventsTestClass
{
    public function KohanaEventsTestObjectMethod(&$args = array())
    {

        $data = array();
        $counts = array();

        if (isset($args['test_instance']))
        {
            $data = &$args['test_instance']->test_data;
            $counts = &$args['test_instance']->test_counts;
        } 
        else
        {
            if (isset($args['data']))
            {
                $data = &$args['data'];
            }

            if (isset($args['counts']))
            {
                $counts = &$args['counts'];
            }   

        }

        $key = 'KohanaEventsTestClass.KohanaEventsTestObjectMethod';

        $data[$key] = TRUE;
        if (!isset($counts[$key])) 
        {
            $counts[$key] = 0;
        }
        $counts[$key]++;
    }

    public static function KohanaEventsTestStaticMethod(&$args = array())
    {
        $data = array();
        $counts = array();

        if (isset($args['test_instance']))
        {
            $data = &$args['test_instance']->test_data;
            $counts = &$args['test_instance']->test_counts;
        } 
        else
        {
            if (isset($args['data']))
            {
                $data = &$args['data'];
            }

            if (isset($args['counts']))
            {
                $counts = &$args['counts'];
            }   

        }

        $key = 'KohanaEventsTestClass.KohanaEventsTestStaticMethod';
        $data[$key] = TRUE;
        if (!isset($counts[$key])) 
        {
            $counts[$key] = 0;
        }
        $counts[$key]++;        
    }    


    public function KohanaEventsDefaultObjectMethod(&$args = array())
    {
        return 'VALUE';
    }


    public static function KohanaEventsDefaultClassMethod(&$args = array())
    {        
        return 'VALUE';
    }

}

function KohanaEventsTestFunction(&$args = array())
{
    
    $data = array();
    $counts = array();
    
    if (isset($args['test_instance']))
    {
        $data = &$args['test_instance']->test_data;
        $counts = &$args['test_instance']->test_counts;
    } 
    else
    {
        if (isset($args['data']))
        {
            $data = &$args['data'];
        }

        if (isset($args['counts']))
        {
            $counts = &$args['counts'];
        }   

    }

    $key = 'KohanaEventsTestFunction';
    $data[$key] = TRUE;
    if (!isset($counts[$key])) 
    {
        $counts[$key] = 0;
    }
    $counts[$key]++;    

}

function KohanaEventsDefaultCallback (&$args = array())
{
    return 'VALUE';
}
