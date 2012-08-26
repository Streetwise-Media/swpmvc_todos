<?php
    
class TodosController extends swpMVCBaseController
{
    public function __construct()
    {
        $this->_templatedir = dirname(__FILE__).'/../views/';
        parent::__construct();
    }
    
    public function before()
    {
        $this->_styles = array(
            array('todos_styles', '/wp-content/plugins/swp_todos/assets/css/style.css')
        );
    }
    
    public function show_todos_list($user_nicename=false)
    {
        if (!$user_nicename) return $this->set404();
        get_header();
        echo 'Hello '.$user_nicename.'!';
        get_footer();
    }
    
    public function edit_todos_list()
    {
        if (!is_user_logged_in()) return header('Location: '.wp_login_url( get_bloginfo('url').'/mytodos' ));
        $this->_scripts = array(
            array('edit_todos', get_bloginfo('url').'/wp-content/plugins/swp_todos/assets/js/edit_todos.js', array('jquery'))  
        );
        $this->_script_localizations = array(
            array('edit_todos', 'todoEdit', array(
                            'update_url' => self::link('TodosController', 'update_todo', array('')),
                            'toggle_url' => self::link('TodosController', 'toggle_todo', array(''))
                        )
                )  
        );
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
}