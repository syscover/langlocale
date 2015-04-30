<?php namespace Syscover\Langlocale\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class SetLangLocaleUser {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!config('langlocale.urlType')) return $next($request);

        if($request->segment(1) != null)
        {
            if(config('langlocale.urlType') == 'langlocale')
            {
                $langLocaleData = explode("-", $request->segment(1));
            }
            elseif(config('langlocale.urlType') == 'lang' || onfig('langlocale.urlType') == 'locale')
            {
                $langLocaleData = $request->segment(1);
            }
        }
        else
        {
            $langLocaleData = [];
        }


        // routine to establish country and language variables in session, with URL data language and country
        if (config('langlocale.urlType') == 'langlocale' && count($langLocaleData) == 2 && in_array($langLocaleData[0], config('langlocale.langs')) && in_array($langLocaleData[1], config('langlocale.countries')))
        {
            session(['langUser'     => $langLocaleData[0]]);
            session(['countryUser'  => $langLocaleData[1]]);
        }
        // when only we need know user language
        elseif(config('langlocale.urlType') == 'lang' && in_array($langLocaleData, config('langlocale.langs')))
        {
            session(['langUser' => $langLocaleData]);
        }
        // when only we need know user country
        elseif(config('langlocale.urlType') == 'locale' && in_array($langLocaleData, config('langlocale.countries')))
        {
            session(['countryUser' => $langLocaleData]);
        }
        // routine to set variables if we have cookies, set in session variables
        elseif($request->cookie('langUser') != null && $request->cookie('countryUser') != null)
        {
            session('langUser',     $request->cookie('langUser'));
            session('countryUser',  $request->cookie('countryUser'));
        }

        // routine to set session variables without cookies
        elseif(session('langUser') == null || session('countryUser') == null)
        {
            if(config('langlocale.urlType') == 'langlocale' || config('langlocale.urlType') == 'lang')
            {
                // Routine to know language
                // get header HTTP_ACCEPT_LANGUAGE if there is this variable,
                // the bots like google don't have this variable, in this case we have to complete language data.
                if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
                {
                    $browserLang = \Syscover\Langlocale\Libraries\Miscellaneous::preferedLanguage(config('langlocale.langs'));

                    // instantiate browser language
                    if(in_array($browserLang, config('langlocale.langs')))
                    {
                        $lang = $browserLang;
                    }
                    else
                    {
                        $lang = config('app.locale');
                    }
                }
                else
                {
                    // in this case, ser default lang
                    $lang = config('app.locale');
                }

                session(['langUser' => $lang]);
            }


            if(config('langlocale.urlType') == 'langlocale' || config('langlocale.urlType') == 'locale')
            {
                // Routine to know country
                // We find out the client's IP
                $ip = \Syscover\Langlocale\Libraries\Miscellaneous::getRealIp();
                $browserCountry = \Syscover\Langlocale\Libraries\Miscellaneous::getCountryIp($ip);

                if (in_array($browserCountry, config('langlocale.countries')))
                {
                    $country = $browserCountry;
                }
                // if is set locale, we get default country from locale
                elseif(config('langlocale.urlType') == 'langlocale' || config('langlocale.urlType') == 'lang')
                {
                    // in the case of not getting a valid country, we take the country as default language
                    $country = config('langlocale.countryLang')[$lang];
                }
                else
                {
                    $country = config('langlocale.defaultCountry');
                }

                session(['countryUser' => $country]);
            }
        }

        if(config('langlocale.urlType') == 'langlocale' || config('langlocale.urlType') == 'lang')
        {
            // we establish the language environment
            App::setLocale(session('langUser'));
        }

        return $next($request);
    }
}