<?php
if (!defined('MEDIAWIKI')) die(0);

class SpecialWeRelateBook extends SpecialPage {

    private $output_filepath;

    private $output_filename;

    function __construct() {
        parent::__construct('WeRelateBook');
    }

    function execute( $sourcePageName ) {
		global $wgScriptPath;
        $this->setHeaders();
        $title = null;
        if (!empty($sourcePageName)) {
            $title = Title::newFromText($sourcePageName);
            //if (!$this->setupDir($title)) return false;
            $page = WikiPage::factory($title);
            if (!$page->exists()) {
                $this->getOutput()->addHTML('<div class="error">'.wfMessage('nopagetext').'</div>');
            } else {
 				$latex = new WeRelateBook_Latex($title);
				$treebranch = new WeRelateTreebranch_treebranch($title);
				$treebranch->addObserver(array($this, 'visitPage'));
				$treebranch->addObserver(array($latex, 'visitPage'));
				$treebranch->traverse();

				// Get the PDF file
				$filename = $latex->generatePdf();
				if (!$filename) {
					$this->getOutput()->addHTML('<p class="error">Error: unable to export</p>');
				} else {
					$link = $wgScriptPath.'/images/werelatebook/'.$filename.'.pdf';
					$this->getOutput()->addHTML('<p><a href="'.$link.'">Download PDF</a></p>');
				}

                return;
            }
        }
    }

    public function visitPage(Title $title) {
   		$person = new WeRelateCore_person($title);
        $this->getOutput()->addWikiText('* [['.$person->getTitle().'|'.$person->getFullName().']]');
    }

//     /**
//      * @param WeRelateCore_person
//      */
//     public function getAncestors($person) {
// 		$this->getPerson($person);
// 		foreach ($person->getFamilies('child') as $family) {
// 			if ($this->updateFromRemote($family->getTitle())) {
// 				if ($h = $family->getSpouse('husband')) $this->getAncestors($h);
// 				if ($w = $family->getSpouse('wife')) $this->getAncestors($w);
// 			}
// 		}
//     }
// 
//     /**
//      * @param WeRelateCore_person
//      */
//     public function getDescendants($person) {
// 		$this->getPerson($person);
// 		foreach ($person->getFamilies('spouse') as $family) {
// 			if ($this->updateFromRemote($family->getTitle())) {
// 				foreach ($family->getChildren() as $child) {
// 					$this->getDescendants($child);
// 				}
// 			}
// 		}
//     }


//     function setupDir(Title $title) {
//         // Construct output location
//         global $wgUploadDirectory;
//         $dest = $wgUploadDirectory.DIRECTORY_SEPARATOR.'werelate'.DIRECTORY_SEPARATOR.'book';
//         if ( ! is_dir( $dest ) ) { mkdir( $dest, 0700, true ); } // create directory if it isn't there
//         if ( ! is_dir( $dest ) ) {
// 			// Give up if it still doesn't exist
//             $this->getOutput()->addHTML('<div class="error">Unable to create directory '.$dest.'</div>');
//             return false;
//         }
//         $this->output_filepath = realpath($dest).DIRECTORY_SEPARATOR;
//          // Clean up pagename (special chars etc)
//         $urlText = $title->getPrefixedURL();
//         return $this->output_filename = str_replace( array('\\', '/', ':', '*', '?', '"', '<', '>', "\n", "\r" ), '_', $urlText);
// 	}



//     protected function buildLatex(Title $title) {
//         global $wgScriptPath;
// 
//         // Get the people
//         $people = array();
//         $treebranch = new WeRelateTreebranch_treebranch($title);
// 		foreach (array('ancestors', 'descendants') as $dir) {
// 			foreach ($treebranch->$dir() as $person) {
// 				$people[$this->id($person->getFullname())] = $person;
// 			}
// 		}
// 		ksort($people);
// 		
// 		// Get the LaTeX source
// 		ob_start();
//         require_once __DIR__.'/latex.php';
//         $tex = ob_get_clean();
// 		echo '<pre>'.$tex;exit();
//         // Get the PDF
//         $filename = $this->generatePdf($tex);
//         if (!$filename) {
//             $this->getOutput()->addHTML('<p class="error">Error: unable to export</p>');
//         } else {
//             $link = $wgScriptPath.'/images/printablewerelate/'.$filename.'.pdf';
//             $this->getOutput()->addHTML('<p><a href="'.$link.'">Download PDF</a></p>');
//         }
//     }
// /*
//     /**
//      * Generate PDF from given tex source.
//      * 
//      * @param string $out LaTeX source code.
//      * @return string The output filename with no path or file extension.
//      */
//     public function generatePdf($out) {
//         // Clear out old files
//         wfShellExec('rm '.$this->output_filepath.$this->output_filename.'.*');
//         // Save tex file
//         $tex_filename = $this->output_filepath.$this->output_filename.'.tex';
//         file_put_contents($tex_filename, $out);
//         // Create and execute pdflatex command
//         $pdflatex_cmd = $this->getPdflatexCmd()
//             ." -output-directory=".wfEscapeShellArg(dirname($tex_filename))
//             .' '.wfEscapeShellArg($tex_filename);
//         $shell_out = wfShellExec($pdflatex_cmd);
//         // Make sure a PDF was created
//         $pdf_filename = $this->output_filepath.$this->output_filename.'.pdf';
//         if (!file_exists($pdf_filename)) {
//             wfDebug($shell_out);
//             wfDebug("PDF not generated: $pdf_filename");
//             wfDebug("Command was: $pdflatex_cmd");
//             return false;
//         }
//         // Twice more, for crossreferences.
//         wfShellExec($pdflatex_cmd);
//         wfShellExec($pdflatex_cmd);
//         // Return the output filename with NO file extension.
//         return $this->output_filename;
//     }
// 
//     /**
//      * Get the full path to the 'pdflatex' command.
//      * 
//      * @global string $wgPrintableWeRelate_PdflatexCmd Defined in LocalSettings.php
//      * @return string
//      */
//     public function getPdflatexCmd() {
//         global $wgPrintableWeRelate_PdflatexCmd;
//         $path = 'pdflatex';
//         if (isset($wgPrintableWeRelate_PdflatexCmd) && !empty($wgPrintableWeRelate_PdflatexCmd)) {
//             wfDebug('User-supplied path to pdflatex is: '.$wgPrintableWeRelate_PdflatexCmd);
//             $path = $wgPrintableWeRelate_PdflatexCmd;
//         }
//         return $path;
//     }
// 
//     public function tex_esc($str)
//     {
//         $patterns = array(
//             '/\\\(\s)/' => '\textbackslash\ $1',
//             '/\\\(\S)/' =>  '\textbackslash $1',
//             '/&/'       => '\&',
//             '/%/'       => '\%',
//             '/\$/'      => '\textdollar ',
//             '/>>/'      =>  '\textgreater\textgreater ',
//             '/\^/'      => '\^',
//             '/#/'       => '\#',
//             '/"(\s)/'   => '\'\'$1',
//             '/"(\S)/'   =>  '``$1',
//             '/_/'       =>  '\_',
//             '/http.:\/\/(\S*)/' => '\url{$1}',
//         );
//         return preg_replace(array_keys($patterns), array_values($patterns), $str);
//     }*/

}
