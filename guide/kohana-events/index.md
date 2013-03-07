# Kohana Events

 This class implements an event/trigger calling system.  functions or class/methods can be assigned to a event 'key' and will be run when that point in the code is called.  
 Variables are passed by reference so that external functions can modify internal variables directly
 

    Event::register('user_login','my_custom_function');
    
    Event::trigger('user_login',$User);

    function my_custom_function($User) {
        set_cookie('custom',$User->id);
        $User->custom_cookie_set = TRUE;
    }