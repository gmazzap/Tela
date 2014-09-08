(function($, Tela, QUnit) {

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

})(jQuery, TelaAjax, QUnit);
