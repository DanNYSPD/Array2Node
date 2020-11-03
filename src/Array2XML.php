<?php
namespace Xarenisoft\Array2Node;

use DOMNode;
use Exception;
use DOMElement;
use DOMDocument;
/**
 * Array2Node: A class to convert array in PHP to Node
 * It also takes into account attributes names unlike SimpleXML in PHP
 * It returns the XML in form of DOMDocument class for further manipulation.
 * It throws exception if the tag name or attribute name has illegal chars.
 *
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (10 July 2011)
 * Version: 0.2 (16 August 2011)
 *          - replaced htmlentities() with htmlspecialchars() (Thanks to Liel Dulev)
 *          - fixed a edge case where root node has a false/null/0 value. (Thanks to Liel Dulev)
 * Version: 0.3 (22 August 2011)
 *          - fixed tag sanitize regex which didn't allow tagnames with single character.
 * Version: 0.4 (18 September 2011)
 *          - Added support for CDATA section using @cdata instead of @value.
 * Version: 0.5 (07 December 2011)
 *          - Changed logic to check numeric array indices not starting from 0.
 * Version: 0.6 (04 March 2012)
 *          - Code now doesn't @cdata to be placed in an empty array
 * Version: 0.7 (24 March 2012)
 *          - Reverted to version 0.5
 * Version: 0.8 (02 May 2012)
 *          - Removed htmlspecialchars() before adding to text node or attributes.
 * Version: 0.9 (20 Oct 2020)
 *          - Soported at symbol as a prefix to indicate that a json field is an attribute
 *          - Soported @namespace 
 *          - soported add array to an existing node
 * Version 1.0 
 *           - Added php namespace
 *
 */

class Array2XML {

    /**
     * @var DOMDocument
     */
    private static $xml = null;
    private static $encoding = 'UTF-8';
    private static $firstNode=null;

    /**
     * Initialize the root XML node [optional]
     * @param $version
     * @param $encoding
     * @param $format_output
     */
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true):void {
        self::$xml = new DomDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
        self::$xml->preserveWhiteSpace = false;

        self::$encoding = $encoding;
    }

    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DomDocument
     */
    public static function &createXML($node_name, $arr = array()):DOMDocument {
        $xml = self::getXMLRoot();
        $nodeWrapper=self::convert($node_name, $arr);
      #  $xml->appendChild($nodeWrapper->node);
        $nodeWrapper->removeNamespacev2();

        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $xml;
    }
    public static function startsWith($haystack, $needle):bool
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
    private static function resolveName(array $mainNamespace,string $node_name):string{
        return isset($mainNamespace['prefix'])?$mainNamespace['prefix'].":".$node_name:
            $node_name;
    }

    private static function addAttributeNS(DOMDocument $xml,DOMElement $node,array $attrNs){
        foreach ($attrNs as $attr) {
            echo \implode(",",[$attr['url'],$attr['name'],$attr['value']])."\n";
            $node->setAttributeNS($attr['url'],$attr['name'],$attr['value']);
         // $xml->createAttributeNS( '{namespace_uri_here}', 'example:attr' );
           # $xml->appendChild(
           #  $xml->createAttributeNS($attr['url'],$attr['name']);
           # );
        }
    }
    /**
     * Convert an Array to XML
     * @param string $node_name - name of the roanot node to be converted
     * @param array $arr - aray to be converterd
     * @return DOMNode
     */
    private static function &convert(string $node_name, $arr=array(),bool $useModeAt=true,$namespaceParent=null,$parentNodeToAppend=null):NodeWrapper {
        $nodeWrapper=new NodeWrapper();
        $nameElement=false;
        //print_arr($node_name);
        $xml = self::getXMLRoot();
        $namespaceToInherit=null;
        if(isset($arr['@namespaces'])||!empty($namespaceParent)){
            //we take the first one

            $mainNamespace=isset($arr['@namespaces'])?$arr['@namespaces']["el"]:$namespaceParent["el"];
            $attrNamespace=isset($arr['@namespaces']["attr"])?$arr['@namespaces']["attr"]:($namespaceParent["attr"]??null);
            if(isset($mainNamespace['prefix'])){            
                $node=$xml->createElementNS($mainNamespace['url'], self::resolveName($mainNamespace,$node_name), '');
                $nameElement=true;
              #  $xml->documentElement->removeAttributeNS(self::resolveName($mainNamespace,$node_name));
                if(self::$firstNode!=null){
                    #self::$firstNode->removeAttributeNS(self::resolveName($mainNamespace,$node_name),$mainNamespace['url']);
                    #self::$firstNode->removeAttributeNS($mainNamespace['url'],$mainNamespace['prefix']);
                    #$nodeWrapper->remove[]=[$mainNamespace['url'],$mainNamespace['prefix']];
                    $nodeWrapper->remove[]=$mainNamespace;
                }
                if(isset($attrNamespace)){
                    self::addAttributeNS($xml,$node,$attrNamespace);
                    unset($attrNamespace);
                }
                

            }else{
                //in this case it only has a url but not an alias/prefix
                $node = $xml->createElement($node_name);
                $node->setAttributeNS($mainNamespace['url']);
            }
            if(isset($mainNamespace['inherit']) && $mainNamespace['inherit']==true){
                $namespaceToInherit=$arr['@namespaces']??$namespaceParent;
                if(isset($namespaceToInherit['attr'])){
                    unset($namespaceToInherit['attr']);
                }
            }
            if(isset($arr['@namespaces'])){
                unset($arr['@namespaces']);
            }
            
         }else{
            $node = $xml->createElement($node_name);
        }
        $nodeWrapper->node=$node;
        if(self::$firstNode==null){
            self::$firstNode=$node;
            $xml->appendChild($node);
        }
        if($parentNodeToAppend){
            $parentNodeToAppend->appendChild($node);
        }
        if(is_array($arr)){
            if($useModeAt){
                foreach ($arr as $key => $value) {
                    if(self::startsWith($key,'@')&& (is_scalar($arr[$key])|| null==$arr[$key])){
                        $keyReal=substr($key,1);
                        $node->setAttribute($keyReal, self::bool2str($value));
                        unset($arr[$key]); //remove the key from the array once done.
                    }
                }
            }
            // get the attributes first.;
            if(isset($arr['@attributes'])) {
                foreach($arr['@attributes'] as $key => $value) {
                    if(!self::isValidTagName($key)) {
                        throw new Exception('[Array2XML] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr['@attributes']); //remove the key from the array once done.
            }
           
            /*
            if(isset($arr['@namespaces'])){
               foreach ($arr['@namespaces'] as $value) {
                    $element = $xml->createAttributeNS($value['url'], $value['prefix']);
                    $node->appendChild($element);
               }
               unset($arr['@namespaces']); //remove the key from the array once done.
            }
           */

            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if (isset($arr['@value'])) {
                $node->appendChild($xml->createTextNode(self::bool2str($arr['@value'])));
                unset($arr['@value']);    //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                #return $node;
                return $nodeWrapper;
            } else if (isset($arr['@cdata'])) {
                $node->appendChild($xml->createCDATASection(self::bool2str($arr['@cdata'])));
                unset($arr['@cdata']);    //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
              #  return $node;
              return $nodeWrapper;
            }
            
        }

        //create subnodes using recursion
        if (is_array($arr)) {
            // recurse to get the node for that key
            foreach ($arr as $key => $value) {
                if (!self::isValidTagName($key)) {
                    throw new Exception('[Array2XML] Illegal character in tag name. tag: ' . $key. ' in node: '. $node_name);
                }
                if (is_array($value) && is_numeric(key($value))) {
                    // MORE THAN ONE NODE OF ITS KIND;
                    // if the new array is numeric index, means it is array of nodes of the same kind
                    // it should follow the parent key name
                    foreach ($value as $k => $v) {
                        $nodeWraChild=self::convert($key, $v,$useModeAt,$namespaceToInherit,$node);
                        $node->appendChild($nodeWraChild->node);
                        #$nodeWraChild->removeNamespacev1();
                        $nodeWrapper->mergeRemove($nodeWraChild->removeNamespacev2($xml));
                        
                        
                    }
                } else {
                    // ONLY ONE NODE OF ITS KIND
                    $nodeWraChild=self::convert($key, $value,$useModeAt,$namespaceToInherit,$node);
                    $node->appendChild($nodeWraChild->node);
                    $nodeWrapper->mergeRemove($nodeWraChild->removeNamespacev2($xml));
                    #$nodeWraChild->removeNamespacev1();
                }
                unset($arr[$key]); //remove the key from the array once done.
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if (!is_array($arr)) {
            $node->appendChild($xml->createTextNode(self::bool2str($arr)));
        }

        //return $node;
        return $nodeWrapper;
    }

    /*
     * Get the root XML node, if there isn't one, create it.
     */
    private static function getXMLRoot(): DOMDocument{
        if (empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }

    /*
     * Get string representation of boolean value
     */
    private static function bool2str($v) {
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;
        return $v;
    }

    /*
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     */
    private static function isValidTagName($tag) {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}

