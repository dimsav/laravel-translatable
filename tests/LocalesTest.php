<?php

use Dimsav\Translatable\Locales;

class LocalesTest extends TestsBase
{
    public function test_singleton()
    {
        $this->assertSame(spl_object_id($this->app->make('translatable.locales')), spl_object_id($this->app->make('translatable.locales')));
        $this->assertSame(spl_object_id($this->app->make(Locales::class)), spl_object_id($this->app->make(Locales::class)));
        $this->assertSame(spl_object_id($this->app->make('translatable.locales')), spl_object_id($this->app->make(Locales::class)));
    }

    public function test_load()
    {
        $this->app['config']->set('translatable.locales', [
            'de',
        ]);
        $this->app->make('translatable.locales')->load();
        $this->assertEquals(['de'], $this->app->make('translatable.locales')->all());

        $this->app['config']->set('translatable.locales', [
            'de',
            'en',
        ]);
        $this->assertEquals(['de'], $this->app->make('translatable.locales')->all());
        $this->app->make('translatable.locales')->load();
        $this->assertEquals(['de', 'en'], $this->app->make('translatable.locales')->all());
    }

    public function test_all_language_locales()
    {
        $this->app['config']->set('translatable.locales', [
            'el',
            'en',
            'fr',
            'de',
            'id',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertEquals(['el', 'en', 'fr', 'de', 'id'], $this->app->make('translatable.locales')->all());
    }

    public function test_all_country_locales()
    {
        $this->app['config']->set('translatable.locales', [
            'en' => [
                'GB',
                'US',
            ],
            'de' => [
                'DE',
                'CH',
            ],
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertEquals(['en', 'en-GB', 'en-US', 'de', 'de-DE', 'de-CH'], $this->app->make('translatable.locales')->all());
    }

    public function test_to_array()
    {
        $this->app['config']->set('translatable.locales', [
            'el',
            'en',
            'fr',
            'de',
            'id',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertEquals(['el', 'en', 'fr', 'de', 'id'], $this->app->make('translatable.locales')->toArray());
    }

    public function test_current_config()
    {
        $this->app['config']->set('translatable.locale', 'de');

        $this->assertEquals('de', $this->app->make('translatable.locales')->current());
    }

    public function test_current_translator()
    {
        $this->app['config']->set('translatable.locale', null);
        $this->app['translator']->setLocale('en');

        $this->assertEquals('en', $this->app->make('translatable.locales')->current());
    }

    public function test_has()
    {
        $this->app['config']->set('translatable.locales', [
            'el',
            'en',
            'fr',
            'de',
            'id',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertTrue($this->app->make('translatable.locales')->has('de'));
        $this->assertFalse($this->app->make('translatable.locales')->has('jp'));
    }

    public function test_offset_exists()
    {
        $this->app['config']->set('translatable.locales', [
            'el',
            'en',
            'fr',
            'de',
            'id',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertTrue(isset($this->app->make('translatable.locales')['de']));
        $this->assertFalse(isset($this->app->make('translatable.locales')['jp']));
    }

    public function test_get()
    {
        $this->app['config']->set('translatable.locales', [
            'el',
            'en',
            'fr',
            'de',
            'id',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertEquals('de', $this->app->make('translatable.locales')->get('de'));
        $this->assertNull($this->app->make('translatable.locales')->get('jp'));
    }

    public function test_offset_get()
    {
        $this->app['config']->set('translatable.locales', [
            'el',
            'en',
            'fr',
            'de',
            'id',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertEquals('de', $this->app->make('translatable.locales')['de']);
        $this->assertNull($this->app->make('translatable.locales')['jp']);
    }

    public function test_add_language_locale()
    {
        $this->app['config']->set('translatable.locales', [
            'de',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertTrue($this->app->make('translatable.locales')->has('de'));
        $this->assertFalse($this->app->make('translatable.locales')->has('en'));
        $this->app->make('translatable.locales')->add('en');
        $this->assertTrue($this->app->make('translatable.locales')->has('en'));
    }

    public function test_offset_set_language_locale()
    {
        $this->app['config']->set('translatable.locales', [
            'de',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertTrue($this->app->make('translatable.locales')->has('de'));
        $this->assertFalse($this->app->make('translatable.locales')->has('en'));
        $this->app->make('translatable.locales')[] = 'en';
        $this->assertTrue($this->app->make('translatable.locales')->has('en'));
    }

    public function test_offset_set_country_locale()
    {
        $this->app['config']->set('translatable.locales', [
            'de',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertTrue($this->app->make('translatable.locales')->has('de'));
        $this->assertFalse($this->app->make('translatable.locales')->has('de-AT'));
        $this->app->make('translatable.locales')['de'] = 'AT';
        $this->assertTrue($this->app->make('translatable.locales')->has('de-AT'));
    }

    public function test_forget()
    {
        $this->app['config']->set('translatable.locales', [
            'de',
            'en',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertTrue($this->app->make('translatable.locales')->has('de'));
        $this->assertTrue($this->app->make('translatable.locales')->has('en'));
        $this->app->make('translatable.locales')->forget('en');
        $this->assertFalse($this->app->make('translatable.locales')->has('en'));
    }

    public function test_offset_unset()
    {
        $this->app['config']->set('translatable.locales', [
            'de',
            'en',
        ]);
        $this->app->make('translatable.locales')->load();

        $this->assertTrue($this->app->make('translatable.locales')->has('de'));
        $this->assertTrue($this->app->make('translatable.locales')->has('en'));
        unset($this->app->make('translatable.locales')['en']);
        $this->assertFalse($this->app->make('translatable.locales')->has('en'));
    }

    public function test_get_locale_separator_config()
    {
        $this->app['config']->set('translatable.locale_separator', '_');

        $this->assertEquals('_', $this->app->make('translatable.locales')->getLocaleSeparator());
    }

    public function test_get_locale_separator_default()
    {
        $this->app['config']->set('translatable.locale_separator', null);

        $this->assertEquals('-', $this->app->make('translatable.locales')->getLocaleSeparator());
    }

    public function test_get_country_locale()
    {
        $this->assertEquals('de-AT', $this->app->make('translatable.locales')->getCountryLocale('de', 'AT'));
    }

    public function test_is_locale_country_based()
    {
        $this->assertTrue($this->app->make('translatable.locales')->isLocaleCountryBased('de-AT'));
        $this->assertFalse($this->app->make('translatable.locales')->isLocaleCountryBased('de'));
    }

    public function test_get_language_from_country_based_locale()
    {
        $this->assertEquals('de', $this->app->make('translatable.locales')->getLanguageFromCountryBasedLocale('de-AT'));
        $this->assertEquals('de', $this->app->make('translatable.locales')->getLanguageFromCountryBasedLocale('de'));
    }
}
