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
            $.post(todoEdit.update_url+$el.attr('id').split('_')[1], {description: $el.val()}, function() {
                $.noop();
            });
        });
        $('.completed input').click(function() {
            var $el = $(this);
            $.get(todoEdit.toggle_url+$el.attr('id').split('_')[1], function() {
                $.noop();
            });
        });
    });
}(jQuery, window));