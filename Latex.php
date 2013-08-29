<?php

class WeRelateBook_Latex {

    private $output_filepath;
    private $output_filename;
    private $images = array();
    private $people = array();

    public function __construct(Title $listPage) {
        // Construct output location
        global $wgUploadDirectory;
        $dest = $wgUploadDirectory.DIRECTORY_SEPARATOR.'werelatebook';
        if ( ! is_dir( $dest ) ) { mkdir( $dest, 0777, true); } // create directory if it isn't there
        $this->output_filepath = realpath($dest).DIRECTORY_SEPARATOR;
         // Clean up pagename (special chars etc)
        $title = $listPage->getPrefixedURL();
        $this->output_filename = str_replace( array('\\', '/', ':', '*', '?', '"', '<', '>', "\n", "\r" ), '_', $title );
    }

    public function visitPage(Title $title) {
        // Get only people pages for now
        if ($title->getNamespace() == NS_WERELATECORE_PERSON) {
			$person = new WeRelateCore_person($title);
			$this->people[$person->getTitle()->getDBkey()] = $person;
        }
    }

   	function label($str) {
		$search = array(' ', '-', '(', ')');
		$replace = array('_');
		return str_replace($search, $replace, strtolower($str));
	}


    public function getOutput() {
        $out = '
\documentclass[a4paper,10pt]{book}
%\usepackage[T1]{fontenc}
\usepackage{url}
\usepackage{graphicx}
\renewcommand{\thesection}{\arabic{section}}
\setcounter{secnumdepth}{0}
\title{Family History}
\begin{document}
\maketitle

\newpage
\null\vspace{\fill}

\thispagestyle{empty}
\begin{center}
Copyright \copyright\ WeRelate contributors

\vspace{1cm}

This book comprises information compiled by the contributors to \url{http://www.WeRelate.org},
and is licensed under a Creative Commons Attribution-ShareAlike 3.0 Unported License.
To view a copy of this license, visit \url{http://creativecommons.org/licenses/by-sa/3.0/}
\end{center}

\vspace{\fill}
\newpage

\tableofcontents
';
        // People
        $out .= "\n\chapter{People}\n";
        foreach ($this->people as $name => $person) {
            $out .= "\n\n".'\section{'.$person->getFullName().'} \label{'.$this->label($name).'}'."\n";

            // Primary Image
            if ($primary_image = $person->getPrimaryImage()) {
				$out .= $this->getImage($primary_image);
            }
//             foreach ($person->getImages() as $image) {
//                 if (isset($image['primary'])) { // && substr($image['filename'], -3)=='jpg') {
//                     $this->getImage($image['filename'], $person->name['given'].' '.$person->name['surname']);
//                 }
//             }

            // Parents
            foreach ($person->getFamilies('child') as $parentFam) {
                foreach (array('Father'=>'husband', 'Mother'=>'wife') as $parent=>$spouse) {
					if ($spouse = $parentFam->getSpouse($spouse) ) {
						$out .= '\textbf{'.$parent.':} '.$spouse->getFullName().' (p.\pageref{'.$this->label($spouse->getTitle()).'}). ';
					}
                }
                $children = array();
				foreach ($parentFam->getChildren() as $child) {
					$child_tex = $child->getFullName();
					$child_label = $child->getTitle()->getDBkey();
					if (isset($people[$child_label])) {
						$child_tex .= ' (p.\pageref{'.$child_label.'})';
					}
					$children[] = $child_tex;
				}
				if (count($children) > 0) {
                    $out .= "\n".'\textbf{Children:} '.join("; ", $children).'.';
                }
            }

            // Events and Facts
            $out .= $this->get_fact_list($person);

            // Biography
            $out .= "\n\n".$this->tex_esc($person->getBody());
 
            // Other images
            $images = '';
            foreach ($person->getImages() as $image) {
                $images .= $this->getImage($image);
            }
            if (!empty($images)) {
                $out .= "\n\n".' \textbf{Images:} '.$images.' ';
            }

        }
        $out .= '
\end{document}
';
		//header('content-type:text');echo $out; exit();
        return $out;
    }

    public function getImage($image) {
        global $wgUploadDirectory;

        // Don't include images more than once.
        $title = $image['title'];
        if (in_array($title->getDBkey(), $this->images)) {
            return '';
        }

        // Build the image page
        $filePage = new WikiFilePage($title);
        if (!$filePage->exists()) {
            return 'Image does not exist (please sync). '.$title->getPrefixedText();
        }
        $imageFile = $filePage->getFile();
        //$thumbName = $imageFile->thumbName(array('width'=>500));
        //$imageFile->createThumb(500);
        // Check file type
        $permittedTypes = array('image/png', 'image/jpeg');
        $type = $imageFile->getMimeType();
        if (!in_array($type, $permittedTypes)) {
            wfDebug("type is $type");
            return "$type files are not able to be included in the PDF output.";
        }

        // Image
        $thumbPath = $wgUploadDirectory.DIRECTORY_SEPARATOR.$imageFile->getThumbRel();
        $dir = pathinfo($thumbPath, PATHINFO_DIRNAME);
        $basename = pathinfo($thumbPath, PATHINFO_FILENAME);
        $ext = pathinfo($thumbPath, PATHINFO_EXTENSION);
        $label = $this->label($title->getDBkey());
        $out = '\begin{figure}'."\n"
            .'\centering'."\n"
            .'\includegraphics[width=0.6\textwidth]{{'.$dir.DIRECTORY_SEPARATOR.$basename.'}.'.$ext.'}'."\n"
            .'\caption{'.$image['caption'].'}'."\n"
            .'\label{'.$label.'}'."\n"
            .'\end{figure}'."\n"
            .'Fig. \ref{'.$label.'} (p.\pageref{'.$label.'}): '.$image['caption']."\n";
        $this->images[] = $title->getDBkey();
        return $out;
    }

    /**
     * Generate PDF from given tex source.
     * 
     * @return string The output filename with no path or file extension.
     */
    public function generatePdf() {
        // Clear out old files
        wfShellExec('rm '.$this->output_filepath.$this->output_filename.'.*');
        // Save tex file
        $tex_filename = $this->output_filepath.$this->output_filename.'.tex';
        file_put_contents($tex_filename, $this->getOutput());
        // Create and execute pdflatex command
        $pdflatex_cmd = $this->getPdflatexCmd()
            ." -output-directory=".wfEscapeShellArg(dirname($tex_filename))
            .' '.wfEscapeShellArg($tex_filename);
        $shell_out = wfShellExec($pdflatex_cmd);
        // Make sure a PDF was created
        $pdf_filename = $this->output_filepath.$this->output_filename.'.pdf';
        if (!file_exists($pdf_filename)) {
            wfDebug($shell_out);
            wfDebug("PDF not generated: $pdf_filename");
            wfDebug("Command was: $pdflatex_cmd");
            return false;
        }
        // Twice more, for crossreferences.
        wfShellExec($pdflatex_cmd);
        wfShellExec($pdflatex_cmd);
        // Return the output filename with NO file extension.
        return $this->output_filename;
    }

    private function get_fact_list($person)
    {
        $out = '';
		// Sources prepared
		$citations = array();
		foreach ($person->getSources() as $citation)
		{
			// Format citation
			$cit = '';
			if ($citation['title']) $cit .= '\emph{'.$this->tex_esc($citation['title']).'} ';
			if ($citation['record_name']) $cit .= '``'.$this->tex_esc($citation['record_name'])."'' ";
			$cit .= $this->tex_esc($citation['body']);
			// Save it for later use
			$citations[$citation['id']] = $cit;
		}

		// Facts
		foreach ($person->getFacts() as $fact) {
			$out .= "\n".'\textbf{'.$fact['type'].':} ';
			$out .= (!empty($fact['date'])) ? $fact['date'] : 'Date unknown';
			if (!empty($fact['place'])) $out .= ', '.$fact['place'];
			if ($fact['desc']) $out .= ' ('.$this->tex_esc($fact['desc']).')';
			$out .= '.';
			// Output sources
			foreach ($fact['sources'] as $source)
			{
				if (isset($citations[$source])) {
					$out .= '\footnote{'.$citations[$source].'} ';
				}
			}
		}
        return $out;
    }

    /**
     * Get the full path to the 'pdflatex' command.
     * 
     * @global string $wgPrintableWeRelate_PdflatexCmd Defined in LocalSettings.php
     * @return string
     */
    public function getPdflatexCmd() {
        global $wgPrintableWeRelate_PdflatexCmd;
        $path = 'pdflatex';
        if (isset($wgPrintableWeRelate_PdflatexCmd) && !empty($wgPrintableWeRelate_PdflatexCmd)) {
            wfDebug('User-supplied path to pdflatex is: '.$wgPrintableWeRelate_PdflatexCmd);
            $path = $wgPrintableWeRelate_PdflatexCmd;
        }
        return $path;
    }

    public function tex_esc($str)
    {
        $patterns = array(
            '/\\\(\s)/' => '\textbackslash\ $1',
            '/\\\(\S)/' =>  '\textbackslash $1',
            '/&/'       => '\&',
            '/%/'       => '\%',
            '/\$/'      => '\textdollar ',
            '/>>/'      =>  '\textgreater\textgreater ',
            '/\^/'      => '\^',
            '/#/'       => '\#',
            '/"(\s)/'   => '\'\'$1',
            '/"(\S)/'   =>  '``$1',
            '/_/'       =>  '\_',
            '/http.:\/\/(\S*)/' => '\url{$1}',
        );
        return preg_replace(array_keys($patterns), array_values($patterns), $str);
    }
    
}
