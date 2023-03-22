<?php


$xml = '<?xml version="1.0" encoding="UTF-8"?>
<response>

<lst name="responseHeader">
  <int name="status">0</int>
  <int name="QTime">0</int>
  <lst name="params">
    <str name="indent">on</str>
    <str name="start">0</str>
    <str name="q">meta_type:207 AND (648_t:인제스트 OR 644_t:인제스트 OR 647_t:인제스트 OR 652_t:인제스트 OR 653_t:인제스트 OR 654_t:인제스트 OR 645_t:인제스트 OR 649_t:인제스트 OR 650_t:인제스트 OR 651_t:인제스트)</str>
    <str name="hl.simple.pre">&lt;span style=\'color: red\'&gt;</str>
    <str name="hl.simple.post">&lt;/span&gt;</str>
    <str name="hl.fl">648_t,644_t,647_t,652_t,653_t,654_t,645_t,649_t,650_t,651_t</str>
    <str name="hl">on</str>
    <str name="fq">status:1 OR status:2</str>
    <str name="rows">20</str>
    <str name="version">2.2</str>
  </lst>
</lst>
<result name="response" numFound="0" start="0"/>
<lst name="highlighting"/>
</response>';

libxml_use_internal_errors(true);
$doc = simplexml_load_string($xml);
if (!$doc)
{
	print_r(libxml_get_errors());
}
?>