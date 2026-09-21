<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Enums\MemberType;
use PHPUnit\Framework\TestCase;

final class EnumsTest extends TestCase
{
    public function test_member_type_has_backed_values_and_friendly_labels(): void
    {
        $this->assertSame('full', MemberType::Full->value);
        $this->assertSame('limited', MemberType::Limited->value);
        $this->assertSame('Full', MemberType::Full->toFriendly());
        $this->assertSame('Limited', MemberType::Limited->toFriendly());
        $this->assertSame(MemberType::Full, MemberType::from('full'));
    }

    public function test_membership_role_has_backed_values_and_friendly_labels(): void
    {
        $this->assertSame('host', MembershipRole::Host->value);
        $this->assertSame('moderator', MembershipRole::Moderator->value);
        $this->assertSame('contributor', MembershipRole::Contributor->value);
        $this->assertSame('Host', MembershipRole::Host->toFriendly());
        $this->assertSame(MembershipRole::Contributor, MembershipRole::from('contributor'));
    }
}
