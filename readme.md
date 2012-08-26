#swpMVC Todos

To get familiar with the swpMVC framework as quickly as possible, we'll walk through the obligatory Todo App example.

We'll leverage as much of the framework as is reasonable, and upon completion we'll aim to have the following pages in our
app:

*   The /todos url will show a listing of all users with todo lists, and each listing links to that users todo lists
*   The /mytodos url will show the todo list of the currently logged in user, or redirect to login page if logged out. When viewing
your own todo list, you will be able to add and edit items on your list.
*   The /todos/{{user\_nicename}} url will show a specific users todo list. When viewing another users todo list, it will be shown in a
read only mode

##Preparing the main plugin file

I love code generation, but unfortunately haven't added any to the swpMVC framework. In settling for the next best thing, we can
start by simply copying the contents of the swpmvc/starter_plugin folder to a new directory where our todos plugin will live.

Once those files have been copied over, open up plugin.php in your editor, and make the following changes:

*   Edit the plugin header comments at top to contain your info.
*   Change the plugin class name, and references to it on lines 12, 26, and 47 from swpMVC\_Starter to swpMVC\_Todos

##Creating a controller

Before we fill out the add\_routes method in our plugin method, we should create a controller for our app that will handle those routes,
and require it in the require\_dependencies method of the plugin class. Create a new file in the controllers subfolder of your plugin
directory called TodosController.php and add the following code:

    <?php
        
        class TodosController extends swpMVCBaseController
        {
        
        }
        
Now that we have the controller file set up, we can require it from the plugin class and send a route there. We'll add methods to the
controller class as we add the routes to make the connections obvious. First add the following code to the require_dependencies
method on line 30 of your plugin.php file:

    <?php
    
        private function require_dependencies()
        {
            require_once(dirname(__FILE__).'/controllers/TodosController.php');
        }
        
##First Route

Our controller is being bootstrapped by the plugin class, so let's create the first route, and then the controller method to handle it.
In the add\_routes method of the swpMVC\_Todos class, on line 40 of plugin.php, add the following code:

    <?php
    
        public function add_routes($routes)
        {
            $routes[] = array(
                'controller' => 'TodosController', 'method' => 'show_todos_list',
                'route' => '/todos/:p'
            );
            return $routes;
        }
        
Now in the TodosController class on line 5 of TodosController.php, let's add the todos_list method that will handle this request, with
some dummy code to make sure it works.

    <?php
    
        public function show_todos_list($user_nicename=false)
        {
            if (!$user_nicename) return $this->set404();
            get_header();
            echo 'Hello '.$user_nicename.'!';
            get_footer();
        }
        
Right now our method simply checks that a parameter has been passed (defined in our route definition by the ":p" tag at the end of
the route.) If not, it returns a 404 page provided by our WordPress theme, and if one is provided, will say hello using the parameter
provided.

To test this out, make sure the Todos plugin is active, and go to the /todos/developer url on our site. You should see a very
underwhelming "Hello developer!" message there. Try a few other routes, like /todos/dawg, /todos/fool, etc, to see the dynamic
goodness, and when you're bored of that, let's move on to more interesting stuff.

##First Real Route

For our app to do anything (no pun,) we need to get some data into the database. Let's get the personal todos interface working so
we can save a list and start seeing things work. First add the route to the add\_routes method in your plugin class as follows:

    <?php
    
        public function add_routes($routes)
        {
            $routes[] = array(
                'controller' => 'TodosController', 'method' => 'show_todos_list',
                'route' => '/todos/:p'
            );
            $routes[] = array(
                'controller' => 'TodosController', 'method' => 'edit_todos_list',
                'route' => '/mytodos'
            );
            return $routes;
        }
        
And now let's add the edit\_todos\_list method to the controller:

    <?php
        
        public function edit_todos_list()
        {
            if (!is_user_logged_in()) return header('Location: '.wp_login_url( get_bloginfo('url').'/mytodos' ));
            echo 'More to come';
        }
        
Right now all this method does is make sure you're logged in. You can test this by logging out of WordPress and going to /mytodos.
You should be redirected to the login page, and on login redirected back to /mytodos. The result is again underwhelming.

In order to get the most out of the process, we'll be using a custom table for our todos, and creating a model for them. Before we
create the interface to edit a todo list, we'll need to take care of those parts.

##Creating a table

This part is not specific to the framework at all, we'll just use
[WordPress conventions](http://codex.wordpress.org/Creating_Tables_with_Plugins).

First add the following method to the swpMVC_Todos class in plugin.php:

    <?php
    
        public static function create_table()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "swpmvc_todos";
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id mediumint(9) NOT NULL,
                description text NOT NULL,
                completed tinyint(1) NOT NULL,
                UNIQUE KEY id (id)
              )
              CHARACTER SET utf8 COLLATE utf8_general_ci;";
              require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
              dbDelta($sql);
        }
        
And then add it to the plugin activation hook at the bottom of plugin.php as follows:

    <?php
    
        register_activation_hook(__FILE__, array('swpMVC_Todos', 'create_table'));
        
Now reactivate your plugin, and check your database. You should see the table there.

##Creating Models

Our edit route needs to look up a list of todos based on user, so we'll need to define the relationship between users and todos. We
can do this by extending the User model that ships with the framework, and amending the $has_many relationship on our extended
model. (For more on relationships and models in general, refer to the
[swpMVC docs](http://streetwise-media.github.com/Streetwise-Wordpress-MVC/#models) and the
[PHP ActiveRecord docs](http://phpactiverecord.org)).

Create a file called todos_models.php in the models subdirectory of your plugin folder, and add the following code:

    <?php
    
        class TodosUser extends User
        {
        
        }
        
Open up wordpress_models.php in the models folder of the swpmvc plugin directory, and find the $has_many definition in the User
class. Copy it, and add it to your TodosUser model, adding a definition for a Todos relationship as follows:

    <?php
    
        class TodosUser extends User
        {
            public static $has_many = array(
                array('posts', 'foreign_key' => 'post_author', 'limit' => 10, 'conditions' => array('post_status = ?', 'publish')),
                array('comments', 'foreign_key' => 'user_id', 'limit' => 10),
                array('meta', 'class' => 'UserMeta', 'foreign_key' => 'user_id'),
                array('todos', 'class' => 'Todos', 'foreign_key' => 'user_id')
            );
        }
        
Now that we've told ActiveRecord that our TodosUser has many todos, which are modeled by the Todo class, let's create the
Todo class, which will model Todos based on the table our plugin has created. Add the following code to your todos_models.php:

    <?php
    
        class Todos extends swpMVCBaseModel
        {
            public static function tablename()
            {
                global $wpdb;
                return $wpdb->prefix.'swpmvc_todos';
            }
            
            public static $belongs_to = array(
                array('user', 'class' => 'TodosUser', 'foreign_key' => 'user_id')
            );
        }
    
We've now told ActiveRecord that our Todos model is based off of the wp_swpmvc_todos table, (with a multisite aware prefix,)
and that it belongs to a property called user, which is modeled using the TodosUser class, and linked up by the value of the user_id
property of a Todos model instance.

Note that for code organization purposes, it's often better to separate your models into their
own files, particularly once you start to add additional logic and they grow in length. For our purposes, these models are simple
enough to keep in one place.

##Defining Model controls

We'll want to take advantage of the models ability to render themselves, so let's
[define the controls](http://streetwise-media.github.com/Streetwise-Wordpress-MVC/#models/public-static-function-controls)
for a Todo item in our Todos class as follows:

    <?php
    
        public static function controls()
        {
            return array(
                'description' => array(
                    'type' => 'input',
                    'label' => 'Todo:'
                ),
                'completed' => array(
                    'type' => 'input',
                    'input_type' => 'checkbox',
                    'label' => 'Completed?'
                )
            );
        }
        
This is a good time to require our models in the require_dependencies method of the swpMVC_Todos class. Update it as follows:

    <?php
    
        private function require_dependencies()
        {
            require_once(dirname(__FILE__).'/controllers/TodosController.php');
            require_once(dirname(__FILE__).'/models/todos_models.php');
        }
        
##New Todo Form View

Now that our Todos Model is aware of the form controls needed to interact with its properties, we can take advantage of this by
creating a view with the correct tags to generate a form that will let us create a new Todo. Create a file called edit_todos.tpl in the
views subdirectory of your plugin folder, and add the below HTML:

    <div id="content">
        <!-- new_todo_form -->
            <h3>Add a Todo</h3>
            <form method="post" action="/todo/create">
                <fieldset>
                    <!-- control_label_description --><!-- /control_label_description -->
                    <!-- control_description --><!-- /control_description -->
                </fieldset>
                <div class="form-actions">
                    <input class="btn btn-primary" type="submit" value="Create" />
                </div>
            </form>
        <!-- /new_todo_form -->
    </div>
    
##Rendering the view

Before we can easily access our views, we need to set the view folder for our controller. Define a \_\_construct method for the
TodosController as follows:

    <?php
    
        public function __construct()
        {
            $this->_templatedir = dirname(__FILE__).'/../views/';
            parent::__construct();
        }

We can now render the view from our controller method. Replace the "more to come" notice in the TodosController edit_todos_list
method with the code below:

    <?php
    
        public function edit_todos_list()
        {
            if (!is_user_logged_in()) return header('Location: '.wp_login_url( get_bloginfo('url').'/mytodos' ));
            get_header();
            echo $this->template('edit_todos')->replace('new_todo_form',
                                    Todos::renderForm($this->template('edit_todos')->copy('new_todo_form'), 'new_todo')
                                );
            get_footer();
        }

Reload /mytodos in your browser, and you should see something a bit more interesting, although it probably could use some styling.

Create an assets subdirectory in your plugins folder, then a css folder in the assets folder, and in there place a style.css file with
the below contents:

    .btn-primary {
            background-color: 
            #2B54FB;
        }
        
    .form-actions, .existing_todo {
        background-color: 
        #ECECEC;
        margin-top: 18px;
        margin-bottom: 18px;
        width: 100%;
    }
    
    .existing_todo {
            display:block;
            overflow:hidden;
    }
    
    .existing_todo .completed {
            float:right;
    }
    
    .existing_todo_desc_input {
            display:none;
    }
    
    .existing_todo.completed_1 {
            text-decoration: line-through;
    }
    
    .todos_user span {
        font-style: italic;
    }

Let's assume we want to include our stylesheet on all of the routes handled by this controller. The easy way to do this is using
the controller [before method](http://streetwise-media.github.com/Streetwise-Wordpress-MVC/#controllers/public-function-before)
and the controller [_styles property](http://streetwise-media.github.com/Streetwise-Wordpress-MVC/#controllers/this-_styles).
Create the following before method to enqueue the stylesheet on all routes handled by the TodosController, adjusting the url
to match your plugin folder structure:

    <?php
    
        public function before()
        {
            $this->_styles = array(
                array('todos_styles', '/wp-content/plugins/swp_todos/assets/css/style.css')
            );
        }

Reload the /mytodos page again and you should see the styles applied to the form.

##Processing form submission

We've pointed our form to /todo/create, so we should probably point a route there and create a controller method to handle it.
Once we're done, we'll refactor the way we define this in the view to prevent us having to update references to the route if we
decide to change that url later.

First add the following to the add_routes method of your swpMVC_Todos class:

    <?php
    
        public function add_routes($routes)
        {
            $routes[] = array(
                    'controller' => 'TodosController', 'method' => 'show_todos_list',
                    'route' => '/todos/:p'
                );
            $routes[] = array(
                    'controller' => 'TodosController', 'method' => 'edit_todos_list',
                    'route' => 'mytodos'
                );
            $routes[] = array(
                    'controller' => 'TodosController', 'method' => 'create_todo',
                    'route' => '/todo/create'
            );
            return $routes;
        }
        
Now we need to create the controller method we've pointed that route to. Add the following to your TodosController:

    <?php
    
        public function create_todo()
        {
            $redirect = self::link('TodosController', 'edit_todos_list');
            if (!is_user_logged_in()) return header('Location: '.$redirect);
            if (!$_POST['new_todo'] or !is_array($_POST['new_todo']) or !isset($_POST['new_todo']))
                return header('Location: '.$redirect);
            $new_todo = new Todos($_POST['new_todo']);
            $new_todo->user_id = get_current_user_id();
            $new_todo->save();
            return header('Location: '.$redirect);
        }
        
With this in place, you can reload your /mytodos page, and submit a few times. We're not showing existing todo items yet, but if
you look at the wp_swpmvc_todos table in your database, you should see the todo items being saved.

Note the [link method](http://streetwise-media.github.com/Streetwise-Wordpress-MVC/#controllers/self-link)
used to generate the redirect. By creating our URLs this way, we will not need to update the references to these routes if
we update the URLs associated with those methods in our plugin routes. Let's go back and refactor the hard coded target URL
in the new todos form.

In your edit_todos.tpl view file, change the form tag to the following:

    <form method="post" action="<!-- target_link --><!-- /target_link -->">
    
And then update the edit_todos method of the TodosController class to populate the target\_link tag as follows:

    <?php
    
        public function edit_todos_list()
        {
            if (!is_user_logged_in()) return header('Location: '.wp_login_url( get_bloginfo('url').'/mytodos' ));
            get_header();
            echo $this->template('edit_todos')->replace('new_todo_form',
                                        Todos::renderForm($this->template('edit_todos')->copy('new_todo_form'), 'new_todo')
                                            ->replace('target_link', self::link('TodosController', 'create_todo'))
                                    );
            get_footer();
        }
        
##Showing existing Todos

Now that we can get data in, it's time to get that data out, and show existing todos in the edit\_todos\_list method. First let's
query for the Todos items, and log the results to the console so we can see what we get back. If you haven't yet, enable the
PHP Quick Profiler on your WordPress installation by adding the following to your wp-config.php file:

    <?php
    
        define('SW_WP_ENVIRONMENT', 'development');
        
Now update the edit\_todos\_list method of the TodosController to the following:

    <?php
    
        public function edit_todos_list()
        {
            if (!is_user_logged_in()) return header('Location: '.wp_login_url( get_bloginfo('url').'/mytodos' ));
            $todos = Todos::all(array('conditions' => array('user_id = ?', get_current_user_id())));
            Console::log($todos);
            get_header();
            echo $this->template('edit_todos')->replace('new_todo_form',
                                        Todos::renderForm($this->template('edit_todos')->copy('new_todo_form'), 'new_todo')
                                            ->replace('target_link', self::link('TodosController', 'create_todo'))
                                    );
            get_footer();
        }
        
Refresh your /mytodos page and look in the Console of the PHP Quick Profiler at the bottom of the page, and you'll see an array
containing any Todos you've submitted using the form we've created. Now that we're getting them from the db, let's update our view
and controller to populate them in the /mytodos route.

Add the following to your edit_todos.tpl file:

    <div id="content">
        <!-- existing_todos -->
            <h3>Existing Todos</h3>
            <div class="existing_todos">
                <!-- existing_todo_form -->
                    <div class="existing_todo">
                        <span class="existing_todo_description"><!-- description --><!-- /description --></span>
                        <div class="existing_todo_desc_input">
                            <!-- control_description --><!-- /control_description -->
                        </div>
                        <div class="completed">
                            <!-- control_completed --><!-- /control_completed -->
                        </div>
                    </div>
                <!-- /existing_todo_form -->
            </div>
            <hr />
        <!-- /existing_todos -->
        (...new todo form markup)

And then update the edit\_todos\_list method of the TodosController as follows:
    
    <?php
    
        public function edit_todos_list()
        {
            if (!is_user_logged_in()) return header('Location: '.wp_login_url( get_bloginfo('url').'/mytodos' ));
            $todos = Todos::all(array('conditions' => array('user_id = ?', get_current_user_id())));
            get_header();
            $existing_todos = '';
            foreach($todos as $todo)
            {
                $todo->form_helper()->_prefix = 'todo_'.$todo->id;
                $existing_todos .= $todo->render($this->template('edit_todos')->copy('existing_todo_form'));
            }
            $output = $this->template('edit_todos')->replace('new_todo_form',
                                        Todos::renderForm($this->template('edit_todos')->copy('new_todo_form'), 'new_todo')
                                            ->replace('target_link', self::link('TodosController', 'create_todo'))
                                    )->replace('existing_todo_form', $existing_todos);
            if (empty($todos)) $output = $output->replace('existing_todos', '');
            echo $output;
            get_footer();
        }
        
Now let's add a little javascript to make our existing todos listing editable. In the assets subdirectory of your plugin folder, create
a folder called js, and in that, place a file called edit\_todos.js with the following contents:

    (function($, exports) {
        $('document').ready(function() {
            $('.existing_todo_description').dblclick(function() {
                $(this).hide();
                $(this).parent().find('.existing_todo_desc_input').css('display', 'inline');
            });
            $('.existing_todo_desc_input input').keyup(function(e) {
                var $el = $(this);
                if(e.keyCode !== 13) return;
                $el.parent().hide().parent().find('.existing_todo_description').text($el.val()).show();
                $.post(todoEdit.update_url+'/'+$el.attr('id').split('_')[1], {description: $el.val()}, function() {
                    $.noop();
                });
            });
            $('.completed input').click(function() {
                var $el = $(this);
                $.get(todoEdit.toggle_url+'/'+$el.attr('id').split('_')[1], function() {
                    $.noop();
                });
            });
        });
    }(jQuery, window));
    
With the javascript written, we need to enqueue it and localize it so it will have access to the todoEdit variable
referenced on lines 11 and 17 of edit\_todos.js, with update_url
and toggle_url properties defined correctly. Add the following to the edit\_todos\_list method of your TodosController:

    <?php
    
        public function edit_todos_list()
        {
            if (!is_user_logged_in()) return header('Location: '.wp_login_url( get_bloginfo('url').'/mytodos' ));
            $this->_scripts = array(
                array('edit_todos', get_bloginfo('url').'/wp-content/plugins/swp_todos/assets/js/edit_todos.js', array('jquery'))  
            );
            $this->_script_localizations = array(
                array('edit_todos', 'todoEdit', array(
                                'update_url' => get_bloginfo('url').'/todo/update', 'toggle_url' => get_bloginfo('url').'/todo/toggle'
                            )
                    )  
            );
            // query for todos and render output
            
Be sure to update the url to your js file to reflect your plugin folder structure.
        
We've invented two new URLs, so let's add the routes and controller methods to handle them. Again we'll then refactor our
script localizations to use the controller::link method rather than hard coding urls.

Add the following to the add\_routes method of your swpMVC_Todos class:

    <?php
    
        public function add_routes($routes)
        {
            //existing routes added
            $routes[] = array(
                    'controller' => 'TodosController', 'method' => 'update_todo',
                    'route' => '/todo/update/:p'
            );
            $routes[] = array(
                        'controller' => 'TodosController', 'method' => 'toggle_todo',
                        'route' => '/todo/toggle/:p'
            );
            return $routes;
        }
        
Add the following two methods to your TodosController to handle these new routes:

    <?php
    
        public function update_todo($id=false)
        {
            if(!$id) return;
            if(!isset($_POST['description']) or !is_string($_POST['description']) or trim($_POST['description']) === '')
                return;
            if (!is_user_logged_in()) return;
            $todo = Todos::find($id);
            if (!$todo) return;
            if ($todo->user_id !== get_current_user_id()) return;
            $todo->description = $_POST['description'];
            $todo->save();
            return;
        }
        
        public function toggle_todo($id=false)
        {
            if(!$id) return;
            if(!is_user_logged_in()) return;
            $todo = Todos::find($id);
            if (!$todo) return;
            if ($todo->user_id !== get_current_user_id()) return;
            $todo->completed = (intval($todo->completed === 0)) ? 1 : 0;
            $todo->save();
            return;
        }
        
Our /mytodos page is now fully functional (could use a delete feature but I'll leave that to you to implement.) Let's go back
and refactor the script localization to use the controller::link methods instead of hard coded URLs. Update the script localization
definitions in the edit\_todos\_list method of the TodosController as follows:

    <?php
    
        $this->_script_localizations = array(
            array('edit_todos', 'todoEdit', array(
                            'update_url' => self::link('TodosController', 'update_todo', array('')),
                            'toggle_url' => self::link('TodosController', 'toggle_todo', array(''))
                        )
                )  
        );
        
And then update the edit_todos.js file to account for the new trailing slashes in the URLs, on line 11

    $.post(todoEdit.update_url+$el.attr('id').split('_')[1], {description: $el.val()}, function() {
    
and on line 17

    $.get(todoEdit.toggle_url+$el.attr('id').split('_')[1], function() {
    
With this in good shape, we can start on the next page of our app.

##Looking at other users todos

The next route we want to take on is the /todos/{{user_nicename}} URL, which should show a read only version of a users todo
list. Add the following route to your swpMVC_Todos->add_routes method:

    <?php
    
        $routes[] = array(
            'controller' => 'TodosController', 'method' => 'show_users_todos',
            'route' => '/todos/:p'
        );
        
And then the following method to your TodosController:

    <?php
    
        public function show_users_todos($user_nicename = false)
        {
            if (!$user_nicename) return $this->set404();
            $user = TodosUser::find(array('conditions' => array('user_nicename = ?', $user_nicename),
                        'include' => 'todos'));
            if (!$user) return $this->set404();
            get_header();
            Console::log($user);
            get_footer();
        }
        
Go to /todos/admin (or whatever your user_nicename is) and you should see a user object in the PHP Quick Profiler Console,
with the related todos nested in the relationships property.

Add a file called show_users_todos.tpl to the views subdirectory of your plugin folder, with the following contents:

    <div id="content">
        <!-- has_todos -->
            <h3><!-- display_name --><!-- /display_name -->s Todos</h3>
            <!-- existing_todos -->
                <div class="existing_todo completed_<!-- completed --><!-- /completed -->">
                    <!-- description --><!-- description -->
                </div>
            <!-- /existing_todos -->
        <!-- /has_todos -->
    </div>
    
Then update the TodosController->show\_users\_todos method to render the view as follows:

    <?php
    
        public function show_users_todos($user_nicename = false)
        {
            if (!$user_nicename) return $this->set404();
            $user = TodosUser::find(array('conditions' => array('user_nicename = ?', $user_nicename),
                        'include' => 'todos'));
            if (!$user) return $this->set404();
            get_header();
            $existing_todos = '';
            foreach($user->todos as $todo)
                $existing_todos .= $todo->render($this->template('show_users_todos')->copy('existing_todos'));
            $output = $user->render($this->template('show_users_todos'))->replace('existing_todos', $existing_todos);
            if ($existing_todos === '')
                $output = $output->replace('has_todos', '<h3>'.$user->display_name.' doesn\'t have any todos yet.</h3>');
            echo $output;
            get_footer();
        }
        
Reload todos/admin and have a look. Easy, huh? Let's take care of that last route and wrap things up.

##Showing an Index of users with Todos

Again we start adding the route in the plugin class add\_routes method:

    <?php
    
        $routes[] = array(
            'controller' => 'TodosController', 'method' => 'index',
            'route' => '/todos'
        );
        
Then we start the controller method and get our data:

    <?php
    
        public function index()
        {
            $ids = _::map(Todos::all(array('select' => 'DISTINCT user_id')), function($td) { return $td->user_id; });
            $users = TodosUser::find($ids, array('include' => 'todos'));
            get_header();
            Console::log($users);
            get_footer();
        }

You'll notice use of \_::map in this method. This functionality is provided by a
[slightly modified version of Underscore PHP](http://streetwise-media.github.com/Streetwise-Wordpress-MVC/#logging-utility/underscore-php)
which is included in the swpMVC framework.
       
Now add the following to a new file called index.tpl in the views subdirectory of your plugin folder:

    <div id="content">
        <h2>Users with Todos</h2>
        <!-- todos_user -->
            <div class="todos_user">
                <a href="<!-- view_link --><!-- /view_link -->"><h3><!-- display_name --><!-- /display_name --></h3></a>
                <span><!-- finished_count --><!-- /finished_count --> complete</span>
                <span><!-- unfinished_count --><!-- /unfinished_count --> remaining</span>
            </div>
        <!-- /todos_user -->
    </div>
    
And update your controller method with the following:

    public function index()
    {
        $ids = _::map(Todos::all(array('select' => 'DISTINCT user_id')), function($td) { return $td->user_id; });
        $users = TodosUser::find($ids, array('include' => 'todos'));
        if (!is_array($users)) $users = array($users);
        get_header();
        $todos_users = '';
        foreach($users as $user)
        {
            $finished_count = count(_::filter($user->todos, function($td) { return intval($td->completed) === 1; }));
            $unfinished_count = count(_::filter($user->todos, function($td) { return intval($td->completed) === 0; }));
            $todos_users .= $user->render($this->template('index')->copy('todos_user'))
                                            ->replace('finished_count', $finished_count)
                                            ->replace('unfinished_count', $unfinished_count)
                                            ->replace('view_link',
                                                    self::link('TodosController', 'show_users_todos', array($user->user_nicename)));
        }
        if ($todos_users === '') $todos_users = '<h3>No users with Todos yet.</h3>';
        echo $this->template('index')->replace('todos_user', $todos_users);
        get_footer();
    }
    
That completes the Todos App tutorial, and hopefully gives you a reasonably in-depth view of the swpMVC framework. For further
detail, see the [API Docs](http://streetwise-media.github.com/Streetwise-Wordpress-MVC/) or
[open an issue](https://github.com/Streetwise-Media/Streetwise-Wordpress-MVC/issues?state=open) on the framework repo
or the repo for this Todo plugin.