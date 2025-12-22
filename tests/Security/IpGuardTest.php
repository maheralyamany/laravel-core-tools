<?php
it('blocks ip', function () {
  config()->set('core-tools.security.blocked_ips', ['1.1.1.1']);
  $r = Illuminate\Http\Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '1.1.1.1']);
  expect((new Maher\CoreTools\Security\Request\IpGuard)->isAllowed($r))->toBeFalse();
});
