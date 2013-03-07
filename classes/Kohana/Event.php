<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This class implements an event/trigger calling system.  functions or class/methods can be assigned to a event 'key' and will be run when that point in the code is called.  
 * Variables are passed by reference so that external functions can modify internal variables directly
 * 
 * Event::register('user_login','my_custom_function');
 * Event::trigger('user_login',$User);
 * function my_custom_function($User) {
 *    set_cookie('custom',$User->id);
 *    $User->custom_cookie_set = TRUE;
 * }
 */

class Kohana_Event 
{
    public static $events = array();

    /**
     * registers a callback to a event key
     * @param $key (string) event identifyer
     * @param $callback (string|function) if this is a string, it must exist as a function.  if it is an array, then it must be callable (passed as [$object,'method'] or ['class','method'])
     * @param $args (array|NULL) variable(s) passed by reference as args to the callback.  these args override any $args sent to callback by the 'trigger' method    
     */
    public static function register($key, $callback, &$args = NULL)
    {
        if (!isset(self::$events[$key])) self::$events[$key] = array();
        self::$events[$key][] = array('callback'=> $callback, 'args'=> &$args);
    }
    
    /**
     * triggers all callbacks for a given event key with $args passed by reference
     * @param $key (string) event identifyer
     * @param $callback (string|function|(class:method)|closure) if this is a string, it must exist as a function.  if it is an array, then it must be callable (passed as [$object,'method'] or ['class','method'])
     */ 
    public static function trigger($key,&$args = array())
    {
        //return null if there are no callbacks assigned to this key
        if (!isset(self::$events[$key]))
        {
            return NULL;
        }
        
        //loop through each callback assigned to thhis key
        foreach (self::$events[$key] as $i => $event)
        {

            //use 'call' args if no args were sent to the initial registration
            if (!empty($event['args'])) $args = &$event['args']; 
            
            //get call type for callback (function, object, or static
            $call_type = Args::call($event['callback'],array($args));
            

            
            try {
                if ($call_type == 'function') 
                {
                    $event['callback']($args);
                }
                else if ($call_type == 'static')
                { 
                    $event['callback'][0]::$event['callback'][1]($args);
                } 
                else if ($call_type == 'object')
                {
                    $event['callback'][0]->$event['callback'][1]($args);
                }
                else if ($call_type == 'closure')
                {
                    $event['callback']($args);
                }                
            } 
            catch (Exception $e) 
            {
                throw new Kohana_Exception('event call for key "'.$key.'" caused exception: '.$e->getMessage());
            }           
        }
    }
    
}