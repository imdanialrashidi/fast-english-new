<?php

namespace App\Http\Controllers;

/**
 * S8 trust pages (scope §6, PUB-02): /about, /cooperation, /faq, /support,
 * /terms, /privacy.
 *
 * Every page labels its Persian copy as DRAFT (owner review required).
 * Where the owner must supply the contact, terms, or privacy text, the
 * page shows an explicit BLOCKED state and invents no details: no support
 * channel, no legal text, no names, no numbers.
 */
class TrustController extends Controller
{
    public function about()
    {
        return response()->view('trust.about');
    }

    public function cooperation()
    {
        return response()->view('trust.cooperation');
    }

    public function faq()
    {
        return response()->view('trust.faq');
    }

    public function support()
    {
        return response()->view('trust.support');
    }

    public function terms()
    {
        return response()->view('trust.terms');
    }

    public function privacy()
    {
        return response()->view('trust.privacy');
    }
}
