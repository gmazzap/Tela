var Tela = {};
(function($, TData, window, Tela) {

    Tela.Ajax = {
        run: function(action, data, configs) {
            if (typeof action !== 'string' || action === '') {
                return false;
            }
            if (configs === null || typeof configs !== 'object') {
                configs = {};
            }
            if (data === null || typeof data !== 'object') {
                data = {};
            }
            if (typeof TData.nonces[action] === 'undefined') {
                TData.nonces[action] = '';
            }
            var settings = $.extend(
                    configs,
                    {
                        url: TData.ajax_url,
                        data: {
                            telaajax_action: action,
                            telaajax_nonce: TData.nonces[action],
                            telaajax_data: data
                        },
                        type: "POST"
                    }
            );
            return $.ajax(settings);
        },
        updateHtml: function(target, action, data, settings) {
            if (settings === null || typeof settings !== 'object') {
                settings = {};
            }
            target = $(target);
            settings.dataType = 'html';
            return this.run(action, data, settings).done(function(html) {
                $(target).html(html);
            });
        },
        jsonEvent: function(jsonEvent, action, data, settings) {
            if (typeof jsonEvent !== 'string' || jsonEvent === '') {
                return;
            }
            if (settings === null || typeof settings !== 'object') {
                settings = {};
            }
            settings.dataType = 'json';
            return this.run(action, data, settings).done(function(result) {
                $(window).trigger(jsonEvent, result);
            });
        }
    };

    Tela.PluginHelpers = {
        init: function(settings) {
            var defaults = {
                action: null,
                data: {},
                ajax: {},
                event: null,
                updateOn: null,
                subject: null,
                done: null,
                fail: null,
                always: null
            };
            settings = $.extend(defaults, settings);
            if (typeof settings.action !== 'string' || settings.action === '') {
                settings.action = $(this).data('tela-action');
            }
            if (typeof settings.event !== 'string' || settings.event === '') {
                settings.event = $(this).data('tela-event');
            }
            if (typeof settings.event !== 'string' || settings.event === '') {
                return this;
            }
            if (typeof settings.subject !== 'string' || settings.subject === '') {
                settings.subject = $(this).data('tela-subject');
            }
            if (typeof settings.subject === 'string' && settings.subject !== '') {
                settings.updateOn = 'subject-event';
            } else if (typeof settings.updateOn !== 'string' || settings.updateOn === '') {
                settings.updateOn = $(this).data('tela-update-on');
            }
            return settings;
        },
        getPostData: function(source, context, args) {
            var postdata = {};
            if ($.isFunction(source)) {
                if (typeof context !== 'object') {
                    context = this;
                }
                if (typeof args === 'undefined') {
                    args = null;
                }
                postdata = source.apply(context, [args]);
            } else if (typeof source === 'object') {
                postdata = source;
            }
            return postdata;
        },
        getSelector: function() {
            var selector = $(this).data('tela-selector');
            if (typeof selector !== 'string' || selector === '') {
                var id = $(this).attr('id');
                if (typeof id === 'string' && id !== '') {
                    selector = '#' + id;
                } else {
                    selector = false;
                }
            } else {
                selector = false;
            }
            return selector;
        },
        response: function(response, settings) {
            if (typeof response !== 'object') {
                return false;
            }
            if ($.isFunction(settings.done)) {
                response.done(settings.done);
            }
            if ($.isFunction(settings.fail)) {
                response.fail(settings.done);
            }
            if ($.isFunction(settings.always)) {
                response.always(settings.always);
            }
            return response;
        }
    };

    $.fn.telaAjax = function(settings) {
        var caller = this;
        settings = Tela.PluginHelpers.init.apply(caller, [settings]);
        var postdata = {};
        switch (settings.updateOn) {
            case 'subject-event' :
                if (typeof settings.subject === 'string' && settings.subject !== '') {
                    var target = this;
                    $(document).on(settings.event, settings.subject, function() {
                        postdata = Tela.PluginHelpers.getPostData.apply(
                                caller,
                                [settings.data, this]
                                );
                        return Tela.Ajax.updateHtml.apply(
                                Tela.Ajax,
                                [target, settings.action, postdata, settings.ajax]
                                );
                    });
                }
                break;
            case 'global-event' :
            case 'document-event' :
                var triggerer = settings.updateOn === 'global-event' ? $(window) : $(document);
                triggerer.on(settings.event, function() {
                    var postdataargs = arguments;
                    postdata = Tela.PluginHelpers.getPostData.apply(
                            caller,
                            [settings.data, caller, postdataargs]
                            );
                    return Tela.Ajax.updateHtml.apply(
                            Tela.Ajax,
                            [caller, settings.action, postdata, settings.ajax]
                            );
                });
                break;
            case 'self-event' :
                $(this).on(settings.event, function() {
                    postdata = Tela.PluginHelpers.getPostData.apply(
                            caller,
                            [settings.data, caller]
                            );
                    return Tela.Ajax.updateHtml.apply(
                            Tela.Ajax, [caller, settings.action, postdata, settings.ajax]
                            );
                });
                break;
            default :
                var response = null;
                var selector = Tela.PluginHelpers.getSelector.apply(caller);
                if (selector) {
                    $(document).on(settings.event, selector, function() {
                        postdata = Tela.PluginHelpers.getPostData.apply(
                                caller,
                                [settings.data, caller]
                                );
                        response = Tela.Ajax.run.apply(
                                Tela.Ajax,
                                [settings.action, postdata, settings.ajax]
                                );
                        return Tela.PluginHelpers.response(response, settings);
                    });
                } else {
                    $(caller).on(settings.event, function() {
                        postdata = Tela.PluginHelpers.getPostData.apply(
                                caller,
                                [settings.data, caller]
                                );
                        response = Tela.Ajax.run.apply(
                                Tela.Ajax,
                                [settings.action, postdata, settings.ajax]
                                );
                        return Tela.PluginHelpers.response(response, settings);
                    });
                }
        }
        return this;
    }
    ;
})(jQuery, TelaAjaxData, window, Tela);