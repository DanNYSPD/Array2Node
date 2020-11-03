<?php 
namespace Xarenisoft\Array2Node;

use DOMDocument;


class NodeWrapper{
    /**
     * Node 
     *
     * @var DOMNode
     */
    public $node;
    public $remove=[];//$mainNamespace['url'],$mainNamespace['prefix']

    
    public function removeNamespacev1(){
        $parentNode=$this->node->parentNode;
        while($parentNode !=null){
            foreach ($this->remove as $namespace) {
                if(isset($namespace['addUp'])){
                    if($parentNode instanceof DOMDocument){
                        $parentNode->documentElement->removeAttributeNS($namespace['url'],$namespace['prefix']);
                        $parentNode=$parentNode->parentNode;
                    }else{
                        $parentNode->removeAttributeNS($namespace['url'],$namespace['prefix']);
                        $parentNode=$parentNode->parentNode;
                    }
            }

            }
        }
    }
    public function removeNamespacev2($xml=null){
        #$xml = Array2XML::getXMLRoot();
        $parentNode=$this->node->parentNode;
        if(empty($this->remove)){
            return [];
        }
       # return [];
        if($parentNode !=null){
            foreach ($this->remove as $namespace) {
                # code...
                if(isset($namespace['addUp'])){
                    if($parentNode instanceof DOMDocument){
                        if($parentNode->documentElement->hasAttributeNS($namespace['url'],$namespace['prefix'])){
                            echo "tiene {$parenNode->documentElement->nodeName} {$namespace['url']}\n";
                        }
                        $parentNode->documentElement->removeAttributeNS($namespace['url'],$namespace['prefix']);
                        
                    }else{
                        $has=$parentNode->hasAttributeNS($namespace['url'],$namespace['prefix']);
                        if($has){
                            echo "tiene $parenNode->nodeName {$namespace['url']}\n";
                        }
                        $res=$parentNode->removeAttributeNS($namespace['url'],$namespace['prefix']);
                        if($parentNode->hasAttribute("xmlns:".$namespace['prefix'])){
                            echo "tiene $parenNode->nodeName {$namespace['url']}\n";
                           
                       }
                       #echo $this->node->getNodePath()."\n";
                       #echo $parentNode->getNodePath()."\n";
                       #echo $parentNode->nodeName."\n";
                       foreach ($parentNode->attributes as $attr) {
                        $name = $attr->nodeName;
                        $value = $attr->nodeValue;
                        #echo "Attribute '$name' :: '$value'<br />\n";
                      }
                      #echo "\n";
                    }
                }
            }            
            //$parentNode=$parentNode->parentNode;
        }
        return $this->remove;
    }
    
    public function mergeRemove(array $removeChild){
        $this->remove=\array_merge($this->remove,$removeChild);
    }
}