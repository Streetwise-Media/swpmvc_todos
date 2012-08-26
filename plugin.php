<?php

/*
Plugin Name: Streetwise Media WordPress MVC Todos Plugin
Plugin URI: http://streetwise-media.com
Description: A simple Todos app
Author: Brian Zeligson
Version: 0.1
Author URI: http://brianzeligson.com
*/

class swpMVC_Todos
{
    
    private static $_instance;
    
    private function __construct()
    {
        $this->require_dependencies();
        $this->add_actions();
    }
    
    public static function instance()
    {
        if (!isset(self::$_instance))
            self::$_instance = new swpMVC_Todos();
        return self::$_instance;
    }
    
    private function require_dependencies()
    {
        require_once(dirname(__FILE__).'/controllers/TodosController.php');
        require_once(dirname(__FILE__).'/models/todos_models.php');
    }
    
    private function add_actions()
    {
        add_filter('swp_mvc_routes', array($this, 'add_routes'));
    }
    
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
        $routes[] = array(
                    'controller' => 'TodosController', 'method' => 'create_todo',
                    'route' => '/todo/create'
            );
        $routes[] = array(
                    'controller' => 'TodosController', 'method' => 'update_todo',
                    'route' => '/todo/update/:p'
        );
        $routes[] = array(
                    'controller' => 'TodosController', 'method' => 'toggle_todo',
                    'route' => '/todo/toggle/:p'
        );
        $routes[] = array(
            'controller' => 'TodosController', 'method' => 'show_users_todos',
            'route' => '/todos/:p'
        );
        $routes[] = array(
            'controller' => 'TodosController', 'method' => 'index',
            'route' => '/todos'
        );
        return $routes;
    }
    
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
}

add_action('swp_mvc_init', array('swpMVC_Todos', 'instance'));
register_activation_hook(__FILE__, array('swpMVC_Todos', 'create_table'));