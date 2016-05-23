<?php
/**
 * @package GalleryAholic
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2013 MicroJoom. All Rights Reserved.
 * @author MicroJoom http://www.microjoom.com
 * @modified by GraphicAholic
 */
defined('_JEXEC') or die;

if (!class_exists('JFormFieldImageSrc')){
	class JFormFieldImageSrc extends JFormField{
		
		protected $type = 'ImageSrc';
		
		protected $forceMultiple = true;
		
		public function getInput(){
			$html = array();
			$class = $this->element['class'] ? ' class="image-src ' . (string) $this->element['class'] . '"' : ' class="image-src"';
			$html[] = '<div id="' . $this->id . '"' . $class . '>';
			$options = $this->getOptions();
			$arr_value = (is_string($this->value) == true && strpos($this->value,',') !== false )?explode(',',$this->value):$this->value;
			$html[] = '<ul id="image_src">';
			$_html = array();

			foreach ($options as $i => $option){
			
				$checked = (in_array((string) $option->value, (array) $arr_value) ? ' checked="checked"' : '');
				$class = !empty($option->class) ? ' class="' . $option->class . '"' : '';
				$disabled = !empty($option->disable) ? ' disabled="disabled"' : '';
				$onclick = !empty($option->onclick) ? ' onclick="' . $option->onclick . '"' : '';
				$temp = '<li><span class="image-move"><span>&middot;</span><span>&middot;</span><span>&middot;</span></span>';
				$temp .= '<input type="checkbox" id="' . $this->id . $i . '" name="' . $this->name . '"' . ' value="'
					. htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8') . '"' .$checked . $class . $onclick . $disabled . '/>';

				$temp .= '<label for="' . $this->id . $i . '"' . $class . '>' . JText::_($option->text) . '</label>';
				$temp .= '</li>';
				$_html[$option->value] = $temp;
			}
			
			$_tmp = array();
			if(!empty($arr_value) && !empty($_html)){
			
				$_arr_key = array_keys($_html);
				$flag = true;	
				for($k = 0; $k < count($arr_value); $k++){
					if(array_search($arr_value[$k], $_arr_key) === false){
						$flag = false;
						break;
					}
				}
				if($flag){
					$_not_exit = array_diff($_arr_key,$arr_value);
					if(!empty($_not_exit)){
						for($i =  0; $i< count($_arr_key); $i++){
							if(isset($_not_exit[$i])){
								array_push($arr_value, $_not_exit[$i]);
							}
						}
					}
					for($j = 0; $j < count($arr_value) ;$j++){
						if(isset($arr_value[$j])){
							$_tmp[] = $_html[$arr_value[$j]];
						}
					}
				}else{
					$_tmp = $_html;
				}
			}else{
				$_tmp = $_html;
			}
		
			$html[] = implode('',$_tmp);
			$html[] = '</ul>';
			$html[] = '</div>';
			$this->addStylesheet();	
			$this->addJavaScript();	
			return implode("\n", $html);
		}

		protected function addJavaScript(){
			$document = JFactory::getDocument();
			$document->addScriptDeclaration("
					window.addEvent('domready', function(){
						try{
							var image_src = $(document.body).getElement('#image_src');
							new Sortables(image_src);
						} catch(e){
							console.log(e);
						}
					});
			");
			return true;
		}
		
		protected function addStyleSheet(){
			$document = JFactory::getDocument();
			$document->addStyleDeclaration("

			");
			return true;
		}	
		
		protected function getOptions(){
			$options = array();
			foreach ($this->element->children() as $option){
				if ($option->getName() != 'option'){
					continue;
				}
				$tmp = JHtml::_(
					'select.option', (string) $option['value'], trim((string) $option), 'value', 'text',
					((string) $option['disabled'] == 'true')
				);
				$tmp->class = (string) $option['class'];
				$tmp->onclick = (string) $option['onclick'];
				$options[] = $tmp;
			}
			reset($options);
			return $options;
		}
		
	};
}