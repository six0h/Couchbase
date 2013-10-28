<?php

/**
 * Factory Class for pumping out configured Couchbase Views 
 *
 * @author HootSuite Media Inc.
 * @author Cody Halovich <cody.halovich@hootsuite.com>
 */
class In_Couchbase_Factory
{
    public function getAutocomplete() {
        $cb = new In_Couchbase_Bucket();
        $cb->setDesignDoc('autocomplete');
        $cb->setDataView('byMemberId');
        return $cb;
    }
}
