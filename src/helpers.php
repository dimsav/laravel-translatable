<?php

if( ! function_exists('getLocales') )
{
	function getLocales() {
		return app('translatable.locales')->getLocales();
	}
}