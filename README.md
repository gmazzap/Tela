
Tela
====

PHP / jQuery tool for super-easy ajax in WordPress.

#What is this?
WordPress have an [ajax API](http://codex.wordpress.org/AJAX_in_Plugins). There are zillions of tutorials on the web, discussion in forums, [questions on WPSE](http://wordpress.stackexchange.com/questions/tagged/ajax) about using ajax in WordPress. The reason is simple: using ajax in Wordpress is not *simple*. 
As an experienced developer you may think it is, but everyday I see people having problems, not understanding how it works or not doing things well.
**Tela** is a tool that helps *junior* developers to use ajax in WordPress using best practices with no efforts, and experienced developers to build advanced ajax-based plugin/themes saving time and on a basis of solid architecture.

#Before Start
Few points that can help you if Tela can be your sort of thing or not, without reading further:

- Tela is **not** a full-working plugin, so you can't install it directly, it is intended to be used as part of larger projects
- If you are not a developer, Tela is not for you. You are just learning coding? Tela can help you a lot, but knowing how ajax works *in general*, and reading - at least - the Codex page about ajax in WordPress is highly recommended
- Tela requirements are higher than WordPress ones: you need PHP 5.4+ and WordPress 3.9+ to use it
- Tela requires [Composer](https://getcomposer.org/) to be used inside your code. If you don't know what Composer is, how use it, than... start learning it, you'll thank me. Start googling "Composer in WordPress" and give a read to [this](http://composer.rarst.net/).

#Ok, but why?
If you are still reading you are a WordPress developer with a vague idea of what Tela is, but why you should use it?

Main reasons:
 - write less PHP code
 - write less JS code
 - write DRY code

##How much code is needed for a single simple ajax task in WordPress?

In this [**Gist**](https://gist.github.com/Giuseppe-Mazzapica/40d924560e098dfbab31)  you can find 2 files, one for the PHP part and one for the JS part of a super-simple ajax action, using all the best practices and recomendations for ajax in WordPress.

Counting both files there are 43 non-comment, relevant, lines of code. And note that **code like that must be wrote again and again for every ajax action**: if you have dozen of actions you have to multiply that code for every action... what a pain!

If you are a newbie developer you are probably scared.

If you are an experienced developer I bet you have often used your own architecture on top of WordPress ajax API to overcome this problem, but:

 - write affordable architecture is not easy, and takes time
 - write reusable architecture is not easy, often developers write things from scratch on every project, and that takes time
 - architectures need to be mainteined, and that takes time
 - affordable architecture need to be tested, and that takes time

**Tela offers a solid, reusable architecture to handle ajax** that take cares of everything.

It is unit tested both on PHP side using [PHPUnit](https://phpunit.de/) and on JS side, using [Qunit](http://qunitjs.com/).

#How Tela works: a preview

 PHP 
 
    $tela = GM\Tela::instance( 'test' );
    $args =  array( 'public' => FALSE, 'side' => 'front', 'send_json' => FALSE );
    $tela->add(
      'my_action',
      function( $data ) {
        return '<p>Hello ' . $data['name'] . '!</p>';
      },
      $args
    );


Javascript

    (function($) {
      $('#greating').telaAjax({
        data: function() { return { name: $('#name').text(); } },
        action: "test::my_action"
      });
    })(jQuery);


HTML

    <div id="name">John</div>
    <div id="greating" data-tela-subject="#sayhello" data-tela-event="click">
    </div>
    <button id="sayhello">Say Hello</button>


Previous code:

 - Generates a nonce specific for the action
 - Adds the javascript needed by Tela, and use `wp_localize_script` to pass to it data needed (the url for ajax entry point and the generated nonce)
 - Adds the WordPress action (`add_action( 'wp_ajax_`...) so that WordPress can handle the ajax request
 - Ensures that the action is added only if an user is logged in (look at `'public' => 'false'`) and that it is added only in frontend (`'side' => 'front'`)
 - When an user click the button with id *"sayhello"* the ajax call happen, and the registered callback is going to be fired and it will receive the name of the user, taken from the div with id *"name"*
 - Before the callback is executed, the nonce is checked and callback does not run if it fails
 - If nonce validation passes, the callback runs and returns an html string that contain the user name
 - That html string is automatically inserted inside the div with id *"greating"*
 - All is done using all WordPress best practices and recomendations

So using, literally, 3 lines of PHP and 3 lines of JS with Tela is possible to do what requires 50 or more lines using standard WordPress code: the code in the Gist linked above, with its 43 lines of code, does quite less.

This is only a very tiny sample of what Tela can do, it has a lot of powerful handy features that make developer life easier.

    
    


