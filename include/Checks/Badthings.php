<?php
namespace ThemeCheck;
class Badthings_Checker extends CheckPart
{
    public function doCheck($php_files, $php_files_filtered, $css_files, $other_files)
    {		
        $this->errorLevel = ERRORLEVEL_SUCCESS;
        $grep = '';
				
				if ($this->id == 'BADTHINGS_GOOGLE_CX' || $this->id == 'BADTHINGS_GOOGLE_PUB')
				{
					if ($this->threatLevel == ERRORLEVEL_CRITICAL) $files = $php_files;
					else $files = array_merge($php_files, $other_files);
				} else {
					$files = $php_files_filtered;
				}
				
        foreach ( $files as $php_key => $phpfile ) {
            if ( preg_match( $this->code, $phpfile, $matches ) ) {
							$filename = tc_filename( $php_key );
							$error = ltrim( trim( $matches[0], '(' ) );
							
							if ($this->id == 'BADTHINGS_BASE64ENC_WP' || $this->id == 'BADTHINGS_BASE64ENC_JO') {
								$bad_lines = tc_preg_lines($this->code, $php_key);
								$grep = '';
								foreach ($bad_lines as $bad_line)
								{
									if (!preg_match('/\$link->setVar\(["\']return["\'], ?base64_encode ?\( ?\$returnURL ?\) ?\);/', $bad_line, $matches2))
									{
										if ( preg_match($this->code, $bad_line, $matches2 ) ) {
												$error = $matches2[0];
												$this_line = str_replace( '"', "'", $bad_line );
												$error = ltrim( $error );
												$pre = ( FALSE !== ( $pos = strpos( $this_line, $error ) ) ? substr( $this_line, 0, $pos ) : FALSE );
												$pre = ltrim( htmlspecialchars( $pre ) );
												$grep .= "<pre> ". $pre . htmlspecialchars( substr( stristr( $this_line, $error ), 0, 75 ) ) . "</pre>";
										}
									} 
								}
								if (empty($grep)) continue;
							} else {
								$grep = tc_preg( $this->code, $php_key ); 
							}

							$this->messages[] = __all('Found <strong>%1$s</strong> in file <strong>%2$s</strong>. %3$s', $error, $filename, $grep);
							$this->errorLevel = $this->threatLevel;
            }
        }
    }
}

class Badthings extends Check
{	
    protected function createChecks()
    {
			$this->title = __all("Security breaches");
			$this->checks = array(
						new Badthings_Checker('BADTHINGS_EVAL', TT_COMMON, ERRORLEVEL_CRITICAL, __all('Use of eval()')													, '/(?<![_|a-z0-9|\.])eval\s?\(/i', 'ut_badthings_eval.zip'),
						new Badthings_Checker('BADTHINGS_SYSTEMCALLS', TT_COMMON, ERRORLEVEL_CRITICAL, __all('Use of PHP system calls')							  , '/[^a-z0-9](?<!_)(popen|proc_open|[^_]exec|shell_exec|system|passthru)\(/', 'ut_badthings_systemcalls.zip'),
						new Badthings_Checker('BADTHINGS_BACKTICKS', TT_COMMON, ERRORLEVEL_CRITICAL, __all('Use of backticks execution operators in PHP code')					, '/`/', 'ut_badthings_backticks.zip'),
						new Badthings_Checker('BADTHINGS_SERVERSETTINGS', TT_COMMON, ERRORLEVEL_CRITICAL, __all('Modification of PHP server settings')		, '/\s?ini_set\(/'								, 'ut_badthings_serversettings.zip'),
						new Badthings_Checker('BADTHINGS_BASE64DEC', TT_COMMON, ERRORLEVEL_CRITICAL, __all('Use of base64_decode()')								, '/base64_decode/'								, 'ut_badthings_base64_decode.zip'),
						new Badthings_Checker('BADTHINGS_BASE64ENC_WP', TT_WORDPRESS, ERRORLEVEL_CRITICAL, __all('Use of base64_encode()')								, '/base64_encode/'								, 'ut_badthings_base64_encode.zip'),
						new Badthings_Checker('BADTHINGS_BASE64ENC_JO', TT_JOOMLA, ERRORLEVEL_WARNING, __all('Use of base64_encode()')								, '/base64_encode/'								, 'ut_badthings_base64_encode.zip'), // On joomla, usee of base64_encode just displays a warning because it may be used in template overrides
						new Badthings_Checker('BADTHINGS_UUDECODE', TT_COMMON, ERRORLEVEL_CRITICAL, __all('Use of uudecode()')											, '/uudecode/ims'									, 'ut_badthings_uudecode.zip'),
						new Badthings_Checker('BADTHINGS_ROT13', TT_COMMON, ERRORLEVEL_CRITICAL, __all('Use of str_rot13()')										, '/str_rot13/ims'								, 'ut_badthings_str_rot13.zip'),
						new Badthings_Checker('BADTHINGS_GOOGLE_CX', TT_COMMON, ERRORLEVEL_WARNING, __all('Presence of Google search code')			, '/cx=[0-9]{21}:/'								, 'ut_badthings_googlesearch.zip'),
						new Badthings_Checker('BADTHINGS_GOOGLE_PUB', TT_COMMON, ERRORLEVEL_WARNING, __all('Presence of Google advertising code')	, '/pub-[0-9]{16}/i'							, 'ut_badthings_googleadvertising.zip'),
						new Badthings_Checker('BADTHINGS_VARIABLEFUNC', TT_COMMON, ERRORLEVEL_CRITICAL, __all('Use of variable functions')						, '/\$[_0-9a-zA-Z]+ *\(/', 'ut_badthings_variablefunctions.zip'),
// var_dump
// print_r
// debug functions		
// PREG_REPLACE_EVAL
		);
    }
}
