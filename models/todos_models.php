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
    }