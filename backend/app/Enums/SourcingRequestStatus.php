<?php

namespace App\Enums;

enum SourcingRequestStatus: string
{
    case Draft = 'draft';
    case PendingModeration = 'pending_moderation';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
