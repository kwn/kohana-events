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
    public $events = array();
    protected static $__instance = NULL;

    /**
     * factory
     * 
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function factory()
    {
        return new Event();
    }

    /**
     * instance
     * 
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function instance()
    {
        if (static::$__instance === NULL)
        {
            static::$__instance = static::factory();
        }
        return static::$__instance;
    }

    /**
     * events
     * 
     * @param mixed $key = NULL send a key to retrieve all callbacks on a specific key.  If no key is sent, an array of all callbacks for all keys is returned
     * @param Event = NULL send along an event instance to apply this event to.  If no instance is set, the default instance is used
     *
     * @access public
     * @static
     *
     * @return array an array of all callbacks
     */
    public static function events($key= NULL, Event $Event = NULL)
    {
        if ($Event === NULL)
        {
            $Event = self::instance();
        }
        if ($key === NULL)
        {
            return $Event->events;
        }
        else if (isset($Event->events[$key]))
        {
            return $Event->events[$key];   
        }
        else
        {
            return NULL;
        }

    }

    /**
     * registers a callback to a event key
     * @param string $key event type identifier
     * @param string|array|closure $callback if this is a string, it must exist as a function.  if it is an array, then it must be callable (passed as [$object,'method'] or ['class','method']). it can also be sent as a closure
     * @param array|NULL $args variable(s) passed by reference as args to the callback.  these args override any $args sent to callback by the 'trigger' method    
     * @param array $options array containing any additional event options (here for future features). current valid options are max-runs
     * @param Event = NULL send along an event instance to apply this event to.  If no instance is set, the default instance is used
     * @return string $id and identifier for this specific event registration, can be used to un-register
     */
    public static function register($key, $callback, &$args = NULL, $options = array(), Event $Event = NULL)
    {
        if ($Event === NULL)
        {
            $Event = self::instance();
        }
        if (!isset($Event->events[$key])) $Event->events[$key] = array();
        $id = uniqid();
        $Event->events[$key][$id] = array('callback'=> $callback, 'args'=> &$args, 'options'=> $options);
        return $id;
    }
    
    /**
     * removes a callback based on an $id returned from event::register
     * @param string $key event type identifier
     * @param string $id event identifier
     * @param Event = NULL send along an event instance to apply this event to.  If no instance is set, the default instance is used     
     * @return boolean return TRUE if they $key.$id was set and removed, return FALSE if $key.$id was not set
     */
    public static function remove($key, $id, Event $Event = NULL)
    {
        if ($Event === NULL)
        {
            $Event = self::instance();
        }        
        if (isset($Event->events[$key][$id]))
        {
            unset($Event->events[$key][$id]);
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * triggers all callbacks for a given event key with $args passed by reference
     * @param $key (string) event identifier
     * @param $callback (string|function|(class:method)|closure) if this is a string, it must exist as a function.  if it is an array, then it must be callable (passed as [$object,'method'] or ['class','method'])
     * @param Event = NULL send along an event instance to apply this event to.  If no instance is set, the default instance is used     
     */ 
    public static function trigger($key,&$args = NULL, Event $Event = NULL)
    {
        if ($Event === NULL)
        {
            $Event = self::instance();
        }           
        //return null if there are no callbacks assigned to this key
        if (!isset($Event->events[$key]))
        {
            return NULL;
        }
        
        //loop through each callback assigned to this key
        foreach ($Event->events[$key] as $i => $event)
        {

            // If no arguments were sent to "trigger" and there were arguments sent to "register"
            if ($event['args'] !== NULL && $args === NULL) 
            {
                $args = &$event['args']; 
            }
            
            try 
            {
                $ref_args = array(&$args);
                $call = Args::call($event['callback'],$ref_args);
            } 
            catch (Exception $e) 
            {
            	throw $e;
                //throw new Kohana_Exception('event call for key "'.$key.'" caused exception: '.$e->getMessage());
            }           
        }
    }
    
}