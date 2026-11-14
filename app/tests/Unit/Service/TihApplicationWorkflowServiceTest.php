<?php

namespace App\Tests\Unit\Service;

use App\Entity\Tih;
use App\Entity\TihApplicationEvent;
use App\Entity\User;
use App\Service\TihApplicationWorkflowService;
use App\Service\TihEmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TihApplicationWorkflowServiceTest extends TestCase
{
    public function testRemovalPreservesRelationsAndHistoryAndIsIdempotent(): void
    {
        $user = (new User())->setRoles(['ROLE_TIH', 'ROLE_BENEVOLE']);
        $admin = new User();
        $tih = (new Tih())->setUser($user)->setApplicationStatus(Tih::STATUS_APPROVED);
        $user->setTih($tih);
        $previous = new TihApplicationEvent($tih, Tih::STATUS_PENDING, $user);
        $tih->addApplicationEvent($previous);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(static fn (callable $work) => $work());
        $em->method('find')->willReturn($tih);
        $em->expects($this->never())->method('remove');
        $em->expects($this->once())->method('persist');
        $mailer = $this->createMock(TihEmailService::class);
        $mailer->expects($this->never())->method('sendApprovalEmail');
        $workflow = new TihApplicationWorkflowService($em, $mailer, new NullLogger());

        $this->assertTrue($workflow->remove(1, $admin));
        $this->assertFalse($workflow->remove(1, $admin));
        $this->assertFalse($workflow->approve(1, $admin));
        $this->assertFalse($workflow->refuse(1, 'Motif', $admin));
        $this->assertFalse($workflow->reopenForReview(1, 'Motif', $admin));
        $this->assertSame($user, $tih->getUser());
        $this->assertSame($tih, $user->getTih());
        $this->assertContains('ROLE_BENEVOLE', $user->getRoles());
        $this->assertNotContains('ROLE_TIH', $user->getRoles());
        $this->assertFalse($tih->isValidate());
        $this->assertCount(2, $tih->getApplicationEvents());
        $event = $tih->getApplicationEvents()->last();
        $this->assertSame(Tih::STATUS_REMOVED, $event->getStatus());
        $this->assertSame($admin, $event->getActor());
        $this->assertNull($event->getEmailStatus());
    }

    public function testExplicitResubmissionPreservesRemovalHistory(): void
    {
        $user = new User();
        $tih = (new Tih())->setUser($user)->setApplicationStatus(Tih::STATUS_REMOVED);
        $removed = new TihApplicationEvent($tih, Tih::STATUS_REMOVED, new User(), source: TihApplicationEvent::SOURCE_ADMIN_REMOVAL);
        $tih->addApplicationEvent($removed);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $workflow = new TihApplicationWorkflowService($em, $this->createMock(TihEmailService::class), new NullLogger());

        $event = $workflow->submitForReview($tih, $user, false, true);

        $this->assertSame(Tih::STATUS_PENDING, $tih->getApplicationStatus());
        $this->assertFalse($tih->isValidate());
        $this->assertSame(TihApplicationEvent::SOURCE_RESUBMISSION, $event->getSource());
        $this->assertSame($user, $event->getActor());
        $this->assertCount(2, $tih->getApplicationEvents());
        $this->assertSame(Tih::STATUS_REMOVED, $removed->getStatus());
    }

    public function testOrdinaryEditCannotReactivateRemovedProfile(): void
    {
        $workflow = new TihApplicationWorkflowService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TihEmailService::class),
            new NullLogger(),
        );
        $this->expectException(\LogicException::class);
        $workflow->submitForReview((new Tih())->setApplicationStatus(Tih::STATUS_REMOVED), new User(), false);
    }
}
