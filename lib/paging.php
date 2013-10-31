<?php

namespace Kiki;

class Paging
{
	private $currentPage = 1;
	private $itemsPerPage = 1;
	private $totalItems = null;

	public function __construct()
	{
	}

	public function reset()
	{
		$this->currentPage = 1;
		$this->itemsPerPage = 1;
		$this->totalItems = null;
	}

	public function setCurrentPage( $currentPage )
	{
		$this->currentPage = $currentPage;
	}

	public function setItemsPerPage( $itemsPerPage )
	{
		if ( $itemsPerPage < 1 )
		{
			Log::error( "itemsPerPage should be at least 1" );
			return;
		}

		$this->itemsPerPage = $itemsPerPage;
	}

	public function setTotalItems( $totalItems )
	{
		$this->totalItems = $totalItems;
	}

	public function firstItem()
	{
		return ( ($this->currentPage-1) * $this->itemsPerPage ) + 1;
	}

	public function lastItem()
	{
		return $this->firstItem() + $this->itemsPerPage - 1;
	}

	public function html()
	{
		$content = null;

		if ( !$this->totalItems )
			return $content;

		$numberOfPages = ceil( $this->totalItems / $this->itemsPerPage );

		if ( $numberOfPages == 1 )
			return $content;

		$onFirstPage = ( $this->currentPage==1 );
		$onLastPage = ( $this->currentPage==$numberOfPages );

		$pageLinks = array();

		if ( !$onFirstPage )
			$pageLinks[] = array( 'id' => $this->currentPage-1, 'title' => _('Previous') );

		for( $i=1; $i<=$numberOfPages; ++$i )
		{
			$isCurrentPage = (int)($i==$this->currentPage);
			$pageLinks[] = array( 'id' => $i, 'title' => $i, 'active' => $isCurrentPage );
		}

		if ( !$onLastPage )
			$pageLinks[] = array( 'id' => $this->currentPage+1, 'title' => _('Next') );

		$template = new Template('parts/paging');
		$template->assign( 'pageLinks', $pageLinks );
		return $template->fetch();
	}
}

?>