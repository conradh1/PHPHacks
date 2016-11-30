<?php
/**
 * @class PagingClass
 * Include this file for paging on a list of records.
 *
 * @Assumption: Request parameter "pg" is reserved for paging.
 *
 * @version Beta
        * license GPL version 3 or any later version.
 * copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Xin Chen
 * @since Beta version 5/27/2011
 */
class PagingClass {  
    
    private $pageSize;
    private $pageButtonCount;
    private $totalCount;
    private $pageCount;
    private $currentPage;
    private $BaseUrl;
    private $showTotal;
    
    /**
    * Default constructor assigns needed env variables.
    *
    * @Assumption: Request parameter "pg" is reserved for paging.
    * 
    * @param totalCount - total number of records.
    * @param curPage    - current page.
    * @param pageSize   - number of records on one page.
    * @param pageButtonCount - number of paging buttons on one page.
    */
    public function __construct($totalCount, $curPage, $pageSize, $pageButtonCount) {
        $this->pageSize = $pageSize;      
        $this->pageButtonCount = $pageButtonCount;  // number of paging buttons.

        $this->totalCount = $totalCount;
        $this->pageCount = ceil($totalCount / $this->pageSize);
      
        $this->currentPage = $curPage; 
        if ($this->currentPage == "") { $this->currentPage = 0; }
        else if ($this->currentPage < 0) { $this->currentPage = 0; }
        else if ($this->currentPage >= $this->pageCount) { $this->currentPage = $this->pageCount - 1; }
      
        // Base URL used by page links. Page parameter should be at the end. E.g. "index.php?pg="
        $baseUrl = $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'];
        if (preg_match("#pg=[0-9]*$#", $baseUrl) > 0) { 
          $this->BaseUrl = preg_replace("#pg=[0-9]*$#", "", $baseUrl) . "pg=";
        } else if ( empty($_SERVER['QUERY_STRING']) ) {
          $this->BaseUrl = $baseUrl . "pg=";
        } else {
          $this->BaseUrl = $baseUrl . "&pg="; 
        }

        $this->showTotal = true;
    }

    /**
    * Get the index of the starting record on the current page.
    */
    public function getStart() { return $this->currentPage * $this->pageSize + 1; }


    /**
    * Get the index of the ending record on the current page.
    */
    public function getEnd() { return (1 + $this->currentPage) * $this->pageSize; }


    /**
    * Control whether to output the "Total" value in the output navigation bar.
    *
    * @param $val - true/false.
    */
    public function setShowTotal($val) { $this->showTotal = $val; }

  
    /**
    * Write the navigation bar of paging buttons.
    */
    function writeNavBar() {
        $PageCount = $this->pageCount;
        $CurrentPageIndex = $this->currentPage;
        $PageButtonCount = $this->pageButtonCount;
        $baseUrl = $this->BaseUrl; 
    
        $DEBUG = 0;
        $lblNext = "Next";
        $lblPrev = "Prev";
        $lblFirst = "First";
        $lblLast = "Last";
    
        $s = "";

        if ($DEBUG) {
            print "pagecount: $PageCount, currentPageIndex: $CurrentPageIndex, ";
            print "PageButtonCount: $PageButtonCount<br>";
        }
  
        $startPage = (floor(($CurrentPageIndex)/$PageButtonCount) * $PageButtonCount);
        if ($DEBUG) print "startpage = $startPage<br>";
    
        $tmp = $PageCount - $PageButtonCount;
        if ($tmp > 0 && $tmp < $startPage) { $startPage = $tmp; }
    
        // First.
        if ($CurrentPageIndex == 0) { $s .= $lblFirst . " "; }
        else { $s .= "<a href=\"" . $baseUrl . "0\">" . $lblFirst . "</a> "; }
       
        // Prev.
        if ($CurrentPageIndex == 0) { $s .= $lblPrev . " "; }
        else 
        { 
            $j = $CurrentPageIndex - 1;
            $s .= "<a href=\"" . $baseUrl . $j . "\">" . $lblPrev . "</a> "; 
        }
      
        // ...
        if ($startPage > 0) { $s .= "<a href=\"" . $baseUrl . ($startPage - 1) . "\">...</a> "; }
    
        for ($i = 0; $i < $PageCount; $i ++) {
            if ($i < $startPage || $i >= $startPage + $PageButtonCount) { continue; }
            if ($i == $CurrentPageIndex) { $s .= " " . (1 + $i); }
            else { $s .= " <a href='" . $baseUrl . $i . "'>". (1 + $i) . "</a>"; }
        }
      
        // ...
        if ($startPage + $PageButtonCount <= $PageCount - 1) {
            $j = $PageButtonCount + $startPage;
            $s .= " <a href=\"" . $baseUrl . $j . "\">...</a> ";      
        }
      
        // Next.
        if ($CurrentPageIndex >= $PageCount - 1) { $s .= " " . $lblNext; } 
        else 
        {
            $j = $CurrentPageIndex + 1;
            $s .= " <a href=\"" . $baseUrl . $j . "\">" . $lblNext . "</a>";
        }
  
        // Last.
        if ($CurrentPageIndex >= $PageCount - 1) { $s .= " " . $lblLast; }
        else { $s .= " <a href=\"" . $baseUrl . ($PageCount - 1) . "\">" . $lblLast . "</a>"; }

        //if ($this->showTotal) { $s .= " [$this->totalCount records, $this->pageCount pages]"; }
        if ($this->showTotal) { $s .= " [Total: $this->totalCount]"; }
  
        return $s;
    }  
}

?>
