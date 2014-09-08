function justACallback() {
    return 'Ok!';
}
(function($, Tela, QUnit) {

    QUnit.module("TelaAjax Callbacks");

    QUnit.test("Test: getAjaxSettings() No custom settings", function(assert) {
        expect(1);
        var expected = {
            url: 'http://example.com/admin-ajax.php?bid=1&action=telaajax_proxy&telaajax=1',
            data: {
                telaajax_is_admin: '0',
                telaajax_action: 'test::get_text',
                telaajax_nonce: 'get_text_nonce',
                telaajax_data: {foo: 'bar'}
            },
            type: "POST"
        };
        var current = Tela.Ajax.getAjaxSettings('test::get_text', {foo: 'bar'});
        assert.deepEqual(current, expected);
    });

    QUnit.test("Test: getAjaxSettings() Merge settings", function(assert) {
        expect(1);
        var expected = {
            url: 'http://example.com/admin-ajax.php?bid=1&action=telaajax_proxy&telaajax=1',
            data: {
                telaajax_is_admin: '0',
                telaajax_action: 'test::get_html',
                telaajax_nonce: 'get_html_nonce',
                telaajax_data: {foo: 'bar', bar: 'baz'}
            },
            type: "POST",
            dataType: 'html'
        };
        var ajaxSettings = {dataType: 'html', type: 'GET', url: 'badurl', data: {bar: 'baz'}};
        var current = Tela.Ajax.getAjaxSettings('test::get_html', {foo: 'bar'}, ajaxSettings);
        assert.deepEqual(current, expected);
    });

    QUnit.asyncTest("Test: run() Execute ajax (text)", function(assert) {
        expect(1);
        var get_text = Tela.Ajax.run('test::get_text', {foo: 'bar', bar: 'baz'}, {fake: true});
        get_text.always(function(data) {
            assert.deepEqual(data, 'moked text request');
            QUnit.start();
        });
    });

    QUnit.asyncTest("Test: run() Execute ajax (html)", function(assert) {
        expect(1);
        var get_html = Tela.Ajax.run('test::get_html', {foo: 'bar', bar: 'baz'}, {fake: true, dataType: 'html'});
        get_html.always(function(data) {
            assert.deepEqual(data, '<p>moked html request</p>');
            QUnit.start();
        });
    });

    QUnit.asyncTest("Test: run() Execute ajax (json)", function(assert) {
        expect(1);
        var get_json = Tela.Ajax.run('test::get_json', {status: 'Success!'}, {fake: true, dataType: 'json'});
        get_json.always(function(data) {
            assert.deepEqual(data.status, 'Success!');
            QUnit.start();
        });
    });

    QUnit.test("Test: updateHtml() Is false if bad target", function(assert) {
        expect(1);
        assert.deepEqual(Tela.Ajax.updateHtml({target: '#I-do-not-exists'}), false);
    });

    QUnit.test("Test: updateHtml() Update html from ajax reponse", function(assert) {
        expect(2);
        var mockedJqXHR = {
            readyState: 1,
            done: function(callback) {
                if ($.isFunction(callback)) {
                    return callback.apply(this, ['<p>Mocked html</p>']);
                }
                return false;
            }
        };
        var jqXHR = Tela.Ajax.updateHtml({target: '#qunit-fixture'}, mockedJqXHR);
        assert.deepEqual($('#qunit-fixture').html(), '<p>Mocked html</p>');
        assert.deepEqual(jqXHR, mockedJqXHR);
    });

    QUnit.test("Test: parseDataMap() Is false if bad target", function(assert) {
        expect(1);
        assert.deepEqual(Tela.Ajax.parseDataMap({target: '#I-do-not-exists'}), false);
    });

    QUnit.test("Test: parseDataMap() (html)", function(assert) {
        expect(2);
        var map_container = '<div id="map-container">' +
                '<p id="first" data-tela-map="foo"></p>' +
                '<p id="second" data-tela-map="bar"></p>' +
                '</div>';
        $(map_container).appendTo($('#qunit-fixture'));
        var map = {foo: "<p>I am Foo</p>", bar: "<p>I am Bar</p>"};
        Tela.Ajax.parseDataMap(map, {target: '#map-container', html: true});
        assert.deepEqual($('#first p').html(), 'I am Foo');
        assert.deepEqual($('#second p').html(), 'I am Bar');
    });

    QUnit.test("Test: parseDataMap() (text)", function(assert) {
        expect(4);
        var map_container = '<div id="map-container">' +
                '<p id="first" data-tela-map="foo"></p>' +
                '<p id="second" data-tela-map="bar"></p>' +
                '</div>';
        $(map_container).appendTo($('#qunit-fixture'));
        var map = {foo: "<p>I am Foo</p>", bar: "<p>I am Bar</p>"};
        Tela.Ajax.parseDataMap(map, {target: '#map-container', html: false});
        assert.deepEqual($('#first p').length, 0);
        assert.deepEqual($('#second p').length, 0);
        assert.deepEqual($('#first').text(), '<p>I am Foo</p>');
        assert.deepEqual($('#second').text(), '<p>I am Bar</p>');
    });

    QUnit.test("Test: jsonEvent() Is false if bad event", function(assert) {
        expect(1);
        assert.deepEqual(Tela.Ajax.jsonEvent(null, 'foo', {}, {}), false);
    });

    QUnit.asyncTest("Test: jsonEvent()", function(assert) {
        expect(1);
        $(window).on('custom_event', function(event, event_data) {
            assert.deepEqual(event_data, {status: 'OK!'});
            QUnit.start();
        });
        var mockedJqXHR = {
            readyState: 1,
            done: function(callback) {
                if ($.isFunction(callback)) {
                    return callback.apply(this, [{status: 'OK!'}]);
                }
                return false;
            }
        };
        Tela.Ajax.jsonEvent('custom_event', 'foo', {}, {}, mockedJqXHR);
    });

    QUnit.module("TelaAjax Plugin Helpers");

    QUnit.test("Test: ensureDataAttrType()", function(assert) {
        expect(20);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('foo', 'string'), 'foo');
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('', 'string'), '');
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType(null, 'string'), null);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType(null, 'boolean'), null);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType(null, 'number'), null);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType(null, 'object'), null);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('', 'boolean'), null);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('', 'number'), null);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('', 'object'), null);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType(false, 'boolean'), false);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('false', 'boolean'), false);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('true', 'boolean'), true);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('true', 'boolean'), true);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType(2, 'number'), 2);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('1', 'number'), 1);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('0', 'number'), 0);
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('{}', 'object'), {});
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType({foo: "bar"}, 'object'), {foo: "bar"});
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('{"foo":"bar"}', 'object'), {foo: "bar"});
        assert.deepEqual(Tela.PluginHelpers.ensureDataAttrType('{foo:bar}', 'object'), null);
    });

    QUnit.test("Test: getSettingByData() For string", function(assert) {
        expect(2);
        var settings = {foo: 'by settings'};
        var html = $('<p data-tela-foo="by html">Foo</p>');
        var result = Tela.PluginHelpers.getSettingByData.apply(html, [settings.foo, 'foo']);
        assert.deepEqual(result, 'by settings');
        settings = {foo: null};
        html = $('<p data-tela-foo="by html">Foo</p>');
        result = Tela.PluginHelpers.getSettingByData.apply(html, [settings.foo, 'foo']);
        assert.deepEqual(result, 'by html');
    });

    QUnit.test("Test: getSettingByData() For boolean", function(assert) {
        expect(2);
        var settings = {foo: true};
        var html = $('<p data-tela-foo="false">Foo</p>');
        var result = Tela.PluginHelpers.getSettingByData.apply(html, [settings.foo, 'foo', 'boolean']);
        assert.deepEqual(result, true);
        settings = {foo: null};
        html = $('<p data-tela-foo="false">Foo</p>');
        result = Tela.PluginHelpers.getSettingByData.apply(html, [settings.foo, 'foo', 'boolean']);
        assert.deepEqual(result, false);
    });

    QUnit.test("Test: getSettingByData() For function", function(assert) {
        expect(4);
        var settings = {foo: 'justACallback'}; // see first line in this file
        var html = $('<p data-tela-foo="">Foo</p>');
        var result = Tela.PluginHelpers.getSettingByData.apply(html, [settings.foo, 'foo', 'func']);
        assert.ok($.isFunction(result));
        assert.deepEqual(result.apply(html), 'Ok!');
        settings = {foo: 'foo'};
        html = $('<p data-tela-foo="justACallback">Foo</p>'); // see first line in this file
        result = Tela.PluginHelpers.getSettingByData.apply(html, [settings.foo, 'foo', 'func']);
        assert.ok($.isFunction(result));
        assert.deepEqual(result.apply(html), 'Ok!');
    });

    QUnit.test("Test: getSettingByData() For object", function(assert) {
        expect(2);
        var settings = {
            foo: {text: 'foo'}
        };
        var html = $('<p data-tela-foo="foo">Foo</p>');
        var result = Tela.PluginHelpers.getSettingByData.apply(html, [settings.foo, 'foo', 'object']);
        assert.deepEqual(result, {text: 'foo'});
        settings = {foo: null};
        html = $('<p data-tela-foo=\'{"text": "foo"}\'>Foo</p>');
        result = Tela.PluginHelpers.getSettingByData.apply(html, [settings.foo, 'foo', 'object']);
        assert.deepEqual(result, {text: 'foo'});
    });

    QUnit.test("Test: init() False if not event or action", function(assert) {
        expect(6);
        var html = $('<p>Foo</p>');
        assert.deepEqual(Tela.PluginHelpers.init.apply(html, [{event: 'foo'}]), false);
        assert.deepEqual(Tela.PluginHelpers.init.apply(html, [{action: 'foo'}]), false);
        assert.deepEqual(Tela.PluginHelpers.init.apply(html, [{event: 'foo', action: ''}]), false);
        assert.deepEqual(Tela.PluginHelpers.init.apply(html, [{event: '', action: 'foo'}]), false);
        assert.deepEqual(Tela.PluginHelpers.init.apply(html, [{event: 'foo', action: false}]), false);
        assert.deepEqual(Tela.PluginHelpers.init.apply(html, [{event: false, action: 'foo'}]), false);
    });

    QUnit.test("Test: init() Set updateOn to subject-event if subject found", function(assert) {
        expect(1);
        var html = $('<p data-tela-subject="foo">Foo</p>');
        var expected = {
            action: 'bar',
            data: {},
            ajax: {},
            html: true,
            parseCb: null,
            event: 'foo',
            updateOn: 'subject-event',
            subject: 'foo',
            done: null,
            fail: null,
            always: null
        };
        var settings = Tela.PluginHelpers.init.apply(html, [{event: 'foo', action: 'bar'}]);
        assert.deepEqual(settings, expected);
    });

    QUnit.test("Test: init() Set updateOn Full", function(assert) {
        expect(1);
        var callback = function(data) {
            $.each(data, function(i, v) {
                data[i] = v + ' - edited';
            });
            return data;
        };
        var done = function(result) {
            return result;
        };
        var expected = {
            event: 'foo',
            action: 'bar',
            data: {test: 'test'},
            ajax: {dataType: 'html'},
            html: false,
            parseCb: callback,
            done: done,
            updateOn: 'subject-event',
            subject: 'foo',
            fail: null,
            always: null
        };
        var args = {
            data: {test: 'test'},
            ajax: {dataType: 'html'},
            html: false,
            parseCb: callback,
            done: done
        };
        var html = $('<p data-tela-subject="foo" data-tela-event="foo" data-tela-action="bar">Foo</p>');
        var settings = Tela.PluginHelpers.init.apply(html, [args]);
        assert.deepEqual(settings, expected);
    });

    QUnit.test("Test: getPostData()", function(assert) {
        expect(2);
        assert.deepEqual(Tela.PluginHelpers.getPostData({source: {foo: 'bar'}}), {foo: 'bar'});
        var args = {
            source: function(data) {
                $.each(data, function(i, v) {
                    data[i] = v + ' - edited';
                });
                return data;
            },
            context: 'body',
            options: {foo: 'test'}
        };
        assert.deepEqual(Tela.PluginHelpers.getPostData(args), {foo: 'test - edited'});
    });

    QUnit.test("Test: getSelector()", function(assert) {
        expect(3);
        assert.deepEqual(Tela.PluginHelpers.getSelector.apply(this), false);
        var html = $('<p data-tela-selector="#foo">Foo</p>');
        assert.deepEqual(Tela.PluginHelpers.getSelector.apply(html), '#foo');
        html = $('<p id="foo">Foo</p>');
        assert.deepEqual(Tela.PluginHelpers.getSelector.apply(html), '#foo');
    });

    QUnit.test("Test: response()", function(assert) {
        expect(3);
        assert.deepEqual(Tela.PluginHelpers.response(), false);
        assert.deepEqual(Tela.PluginHelpers.response({foo: 'bar'}), false);
        var setter = function(callback) {
            if ($.isFunction(callback)) {
                this.callbacks.push(callback);
            }
        };
        var doneCb = function() {
            return 'done';
        };
        var failCb = function() {
            return 'fail';
        };
        var alwaysCb = function() {
            return 'always';
        };
        var jqXHR = {
            readyState: 1,
            done: setter,
            fail: setter,
            always: setter,
            callbacks: []
        };
        var expected = {
            readyState: 1,
            callbacks: [doneCb, failCb, alwaysCb],
            done: setter,
            fail: setter,
            always: setter
        };
        var settings = {done: doneCb, fail: failCb, always: alwaysCb};
        var result = Tela.PluginHelpers.response.apply(jqXHR, [jqXHR, settings]);
        assert.deepEqual(result, expected);
    });

})(jQuery, TelaAjax, QUnit);
