<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Args {

    /**
     * The check method can be used to validate, set default values, and cast argument values
     * 
     * @param array &$args an array of arguments passed by reference to be validated based on the sent $definitions
     * @param array $definitions an array of argument rules. supports: type, default, getdefault, validate, and required
     * @param bool $cast = TRUE.  If true, this method will force-cast all variables in the passed-by-reference $args to their expected type
     *
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function check(array &$args,array $definitions, $cast = TRUE)
    {
        // Loop through all possible arguments
        foreach ($definitions as $key => $def)
        {
            // If this key was not set in the sent arguments
            if (!isset($args[$key]))
            {
                // If a callback is set that will return a default value
                if (isset($def['getdefault']))
                {
                    // Assume no arguments
                    $callback_args = array();

                    // if getdefault is sent as an array with a callback and maybe args
                    if (is_array($def['getdefault']) && isset($def['getdefault']['callback']))
                    {
                        $callback = $def['getdefault']['callback'];
                        if (isset($def['getdefault']['args']))
                        {
                            $callback_args = $def['getdefault']['args'];
                        }
                    }
                    // Assume getdefault is the callback
                    else
                    {
                        $callback = $def['getdefault'];
                    }

                    // Call the callback and expect the desired default value to be returned.  set it on the args
                    $args[$key] = Args::call($callback,$callback_args);
                   
                }     
                // If a raw value should be used for the default value        
                else if (isset($def['default']))
                {
                    // Set the default value
                    $args[$key] = $def['default'];
                } 

                // If this key is required but no value was set in args or by defaults
                if (isset($def['required']) && $def['required'] === TRUE)
                {
                    throw new Kohana_Exception('Required Argument ":arg" was not set', array(':arg'=> $key));
                }
            }

            // If a value was sent for this key, or if one was set by default
            if (isset($args[$key]))
            {
                // If validation rules were sent
                if (isset($def['validate']) && is_array($def['validate']))
                {
                    foreach ($def['validate'] as $rule)
                    {
                        $validate_args = array();
                        // If array, assume: callback, args
                        if (is_array($rule))
                        {
                            // first element is the class
                            $validate = $rule[0];
                            if (isset($rule[1]) && is_array($rule[1]))
                            {
                                // if this is an array of arguments
                                foreach ($rule[1] as $i => $arg)
                                {
                                    // if the value is meant to be sent as an argument
                                    if ($arg === ':value')
                                    {
                                        $rule[1][$i] = $args[$key];
                                    }
                                }
                            }
                            else
                            {
                                $rule[1][0] = $args[$key];
                            }
                            $validate_args = $rule[1];
                        }
                        // If not array, assume 'callback' and insert array(:value) as args
                        else
                        {
                            $validate = $rule;
                            $validate_args = array($args[$key]);
                        }

                        $valid = (bool) Args::call($validate, $validate_args);
                        if ($valid === FALSE)
                        {
                            throw new Kohana_Exception('Argument ":arg" is not valid', array(':arg'=> $key));
                        }
                    }
                }

                // If var types were sent
                if (isset($def['type']))
                {
                    // If a single type was set, add it to a single array
                    if (!is_array($def['type']))
                    {
                        $types = array($def['type']);
                    }
                    else
                    {
                        $types = $def['type'];
                    }

                    $val = $args[$key];

                    $valid = FALSE;
                    // Loop through all types
                    foreach ($types as $type)
                    {

                        // sanity check. If a previous type comparison was ok, break out, though break 2 in switch should always catch them
                        if ($valid) break;

                        // Validate against the current type
                        switch ($type) 
                        {
                            case 'int':
                                if (is_numeric($val)) 
                                {
                                    $valid = TRUE;
                                    if (!is_int($val) && $cast === TRUE) 
                                    {
                                        $args[$key] = (int) $val;
                                    }
                                    break 2;
                                }
                                break;
                            case 'float':
                                if (is_numeric($val)) 
                                {
                                    $valid = TRUE;
                                    if (!is_float($val) && $cast === TRUE) 
                                    {
                                        $args[$key] = (float) $val;
                                    }
                                    break 2;
                                }
                                break;
                            case 'bool':
                                // numbers & strings are evaluated into boolean TRUE or FALSE
                                if (is_numeric($val) || is_bool($val) || strtolower($val) === 'true' || strtolower($val) === 'false') 
                                {
                                    if (is_bool($val))
                                    {
                                        $valid = TRUE;
                                        break 2;
                                    }
                                    else if ($val === '1' || $val === 1 || strtolower($val) === 'true')
                                    {
                                        $valid = TRUE;
                                        if ($cast === TRUE) 
                                        {
                                           $args[$key] = TRUE;
                                        }
                                        break 2;
                                    }
                                    else if ($val === '0' || $val === 0 || strtolower($val) === 'false')
                                    {
                                        $valid = TRUE;
                                        if ($cast === TRUE) 
                                        {
                                           $args[$key] = FALSE;
                                        }
                                        break 2;
                                    }
                                }
                                break;
                            case 'string':
                               // Accepts a string or an object that implements the __toString method
                               if (is_string($val) || $val === '' || (is_scalar($val) && $val === strval($val)) || (is_object($val) && method_exists($val, '__toString'))  ) 
                               {
                                   $valid = TRUE;
                                   if ($cast === TRUE) 
                                   {
                                      if (is_object($val))
                                      {
                                        $args[$key] = (string) $val->__toString();  
                                      }
                                      else
                                      {
                                        $args[$key] = (string) $val;  
                                      }
                                      
                                   }                               
                                   break 2;
                               }
                               break;
                            case 'datetimestr':
                                // Any string that strtotime can parse
                                if (is_string($val) && strtotime($val)) 
                                {
                                    $valid = TRUE;
                                    if ($cast === TRUE) 
                                    {
                                        $args[$key] = (string) $val;
                                    }                                       
                                    break 2;
                                }
                                break;
                            case 'datetime':
                                if ($val instanceof Datetime) 
                                {
                                    $valid = TRUE;
                              
                                    break 2;
                                }
                                break;                            
                            case 'timestamp':
                                if (is_numeric($val) && date('r',$val)) 
                                {
                                   $valid = TRUE;
                                   if ($cast === TRUE) 
                                   {
                                      $args[$key] = (int) $val;
                                   }                                    
                                  break 2;
                                }
                                break;
                            case 'invalues':
                                if (isset($def['values']) && in_array($val, $def['values'])) 
                                {
                                    $valid = TRUE;
                                    break 2;
                                }
                                break;
                            case 'array':
                                if (is_array($val)) 
                                {
                                    $valid = TRUE;
                                    break 2;
                                }
                                break;
                            case 'object':
                                if (is_object($val) && (!isset($def['class']) || (isset($def['class']) && strtolower($def['class']) == strtolower(get_class($val))))) 
                                {
                                    $valid = TRUE;
                                    break 2;
                                }
                                break;
                            case 'null':
                                if ($val === NULL) 
                                {
                                    $valid = TRUE;
                                    break 2;
                                }
                                break;
                        } //End switch
                    } // End foreach types     

                    if ($valid === FALSE)        
                    {
                        throw new Kohana_Exception('Argument ":arg" is not a valid :types', array(':arg'=> $key, ':types'=> $types));
                    }
                } //end isset type
            } // End isset
        } // End foreach $definitions
    }

    /**
     * call will take a valid callback send arguments by reference.
     * 
     * @param callable $callback A string (valid function name), array('class','method'), array($Object,'method'), Closure $C
     * @param array &$args An array of arguments to be passed to the callback in the order they are set in the array
     *
     * @access public
     * @static
     *
     * @return mixed Return value from the call.
     */
    public static function call($callback, &$args = array())
    {
        if (!is_callable($callback))
        {
            throw new Kohana_Exception('Callback is not callable. :callback', array(':callback'=> $callback));
        }

        // If args is not an array, assume it is the first argument
        if (!is_array($args))
        {
            $ref_args = array(0=> &$args);
        }
        // If args was sent as an array
        else
        {
            // Assume all args need to be passed by reference
            $ref_args = array(); 
            foreach($args as $k => &$arg){ 
                $ref_args[$k] = &$arg; 
            }                  
        }
   
        try {
            $return_value = call_user_func_array($callback,$ref_args);

            return $return_value;
        } 
        catch (Exception $e) 
        {
            throw $e;//new Kohana_Exception('Callback caused Exception! :message :error',array(':message'=> $e->getMessage(), ':error', $e));
        }     
    }
}