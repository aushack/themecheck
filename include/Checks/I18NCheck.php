<?php

namespace ThemeCheck;

class I18NCheck_Checker extends CheckPart
{
		public function doCheck($php_files, $php_files_filtered, $css_files, $other_files)
    {
        $this->errorLevel = ERRORLEVEL_SUCCESS;
        
        $error = '';

        // make sure the tokenizer is available
        if ( !function_exists( 'token_get_all' ) ) return true;

        foreach ( $php_files as $php_key => $phpfile )
        {
            $error = '';
            
            $stmts = array();
            
            $search = $phpfile;
			//while ( ( $pos = strpos($search, $this->code) ) !== false ) {
			while ( preg_match( '/\s+' . $this->code . '\s*\(/', $search, $matches, PREG_OFFSET_CAPTURE ) ) {
				$pos = $matches[0][1];
                $search = substr($search,$pos);
                $open=1;
                $i=strpos($search,'(')+1;
                while( $open>0 ) {
                    switch($search[$i]) {
                    case '(':
                            $open++; break;
                    case ')':
                            $open--; break;
                    }
                    $i++;
                }
                $stmts[] = substr($search,0,$i);
                $search = substr($search,$i);
            }

            foreach ( $stmts as $match ) {
                $tokens = @token_get_all('<?php '.$match.';');
                if (!empty($tokens)) {
					$before_coma = true; // true if this is the 1st argument of the function
					
                    foreach ($tokens as $tkey => $token) {
						if ($before_coma == true && is_array($token) && in_array( $token[0], array( T_VARIABLE ) ) ) {
                            $filename = tc_filename( $php_key );
                            $grep = tc_grep( ltrim( $match ), $php_key );
                            $error = "";
                            if(preg_match( '/[^\s]*\s[0-9]+/', $grep, $line))
                            {
                                $error = ( !strpos( $error, $line[0] ) ) ? $grep : '';	
                            }
							
							$var_name = $token[1];
							if(is_array($tokens[$tkey+1]) && $tokens[$tkey+1][0]==T_OBJECT_OPERATOR){
								$var_name .= $tokens[$tkey+1][1];
								
								if(is_array($tokens[$tkey+2]) && $tokens[$tkey+2][0]==T_STRING){$var_name .= $tokens[$tkey+2][1];}
							
								if(is_string($tokens[$tkey+3]) && $tokens[$tkey+3]=="["){
									$var_name .= "[";
									if(is_array($tokens[$tkey+4]) && $tokens[$tkey+4][0]==T_CONSTANT_ENCAPSED_STRING){$var_name .= $tokens[$tkey+4][1];}
									if(is_string($tokens[$tkey+5]) && $tokens[$tkey+5]=="]"){$var_name .= "]";}
								}
							}
							
                            $this->messages[] = __all('Possible variable <strong>%1$s</strong> found in translation function in <strong>%2$s</strong>. Translation function calls should not contain PHP variables. %3$s', $var_name, $filename, $error);
                            $this->errorLevel = $this->threatLevel;
                            break; // stop looking at the tokens on this line once a variable is found
                        }
						if ($token == ',') {$before_coma = false;}
                    }
                }
            }
        }
    }
}

class I18NCheck extends Check
{	
    protected function createChecks()
    {
			$this->title = __all("I18N implementation");
			$this->checks = array(
						new I18NCheck_Checker('I18N_E', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of _e(') , '_e', "ut_i18n__e.zip"),
						new I18NCheck_Checker('I18N_ALL', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of ___all(') , '__', "ut_i18n___.zip"),
						new I18NCheck_Checker('I18N_X', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of _x(') , '_x', "ut_i18n__x.zip"),
						new I18NCheck_Checker('I18N_EX', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of _ex(') , '_ex', "ut_i18n__ex.zip"),
						new I18NCheck_Checker('I18N_ESC_ATTR___ALL', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of esc_attr___all(') , 'esc_attr__', "ut_i18n_esc_attr__.zip"),
						new I18NCheck_Checker('I18N_ESC_ATTR_E', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of esc_attr_e(') , 'esc_attr_e', "ut_i18n_esc_attr_e.zip"),
						new I18NCheck_Checker('I18N_ESC_ATTR_X', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of esc_attr_x(') , 'esc_attr_x', "ut_i18n_esc_attr_x.zip"),
						new I18NCheck_Checker('I18N_ESC_HTML___ALL', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of esc_html___all(') , 'esc_html__', "ut_i18n_esc_html__.zip"),
						new I18NCheck_Checker('I18N_ESC_HTML_E', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of esc_html_e(') , 'esc_html_e', "ut_i18n_esc_html_e.zip"),
						new I18NCheck_Checker('I18N_ESC_HTML_X', TT_WORDPRESS | TT_WORDPRESS_CHILD, ERRORLEVEL_WARNING, __all('Proper use of esc_html_x(') , 'esc_html_x', "ut_i18n_esc_html_x.zip"),
						new I18NCheck_Checker('I18N_UNDERSCORE', TT_COMMON, ERRORLEVEL_WARNING, __all('Proper use of __all(') , '_', "ut_i18n__.zip"),
						new I18NCheck_Checker('I18N_GETTEXT', TT_COMMON, ERRORLEVEL_WARNING, __all('Proper use of gettext(') , 'gettext', "ut_i18n_gettext.zip"),
			);
    }
}