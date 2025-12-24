<?php

declare(strict_types=1);

namespace Maher\CoreTools\Security\Request;

class BaseRequestManager
{
  /**
   * Summary of requestManager
   * @var RequestManager
   */
  private $reqManager;

  public function __construct(RequestManager|null $reqManager = null)
  {
    $this->reqManager = $reqManager;
  }

  /**
   * Get summary of requestManager
   *
   * @return  RequestManager
   */
  public function getReqManager()
  {
    if ($this->reqManager == null)
      $this->reqManager = new RequestManager(request());
    return $this->reqManager;
  }

  /**
   * Set summary of requestManager
   *
   * @param  RequestManager  $reqManager  Summary of requestManager
   *
   * @return  self
   */
  public function setReqManager(RequestManager $reqManager)
  {
    $this->reqManager = $reqManager;

    return $this;
  }
}
