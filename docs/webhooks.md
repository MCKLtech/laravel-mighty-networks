# Mighty Networks webhooks: what is really sent

Examples of every webhook type we have captured: **14 types** from **77 messages**, received on one network in a single session.

## About these examples

- **Everything identifying is made up:** member, network, course, lesson, tag, plan and event ids; names, emails, bios, photos, locations and time zones; course, lesson, tag and plan names; comment and lesson text; addresses and dates.
- **What is real:** event types, keys, nesting, key order, value types, `null`s and empty strings, and values such as `reason`, `progress_percent`, `member_type` and counts.
- **Relationships are kept:** ids that matched in the originals still match here. Recent times are all moved by a fixed amount. Older dates are all replaced by one made-up date.
- **Formatting:** bodies are sent on one line, with `<`, `>` and `&` escaped as `\u003c`, `\u003e` and `\u0026`. They are laid out here for reading.
- `email` was usually `""` and sometimes a full address. We don't know what decides it.

## The request

```http
POST /your/webhook/path HTTP/1.1
Host: app.example.com
Accept: application/json
Accept-Encoding: gzip;q=1.0,deflate;q=0.6,identity;q=0.3
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
User-Agent: Mighty Networks Webhooks
```

- The API key set on the webhook arrives as a Bearer token. With no key set, there is no `Authorization` header.
- There is no signature header.
- Requests also carry tracing headers (`Traceparent`, `Tracestate` and `X-Datadog-*`), left out here.

## The envelope

| Key | Value |
|---|---|
| `event_id` | 64 hexadecimal characters, different for every message, including repeats of the same state |
| `event_timestamp` | ISO 8601 with microseconds and `+00:00` |
| `event_type` | PascalCase ending in `Hook`, such as `MemberJoinedHook` |
| `payload` | Depends on the type: see below |

## Who each message is about

| Types | Member id |
|---|---|
| All `Member…Hook` types | `payload.member.id` |
| `MemberRemovedFromPlanHook` | also `payload.member_id` |
| `ReactionCreatedHook`, `ReactionDeletedHook` | `payload.member_id` |
| `CommentCreatedHook` | `payload.author_id` |

## Types captured

| `event_type` | SDK `WebhookEventType` | Messages captured |
|---|---|---|
| [`MemberJoinedHook`](#memberjoinedhook) | `MEMBER_JOINED` | 13 |
| [`MemberLeftHook`](#memberlefthook) | `MEMBER_LEFT` | 14 |
| [`MemberUpdatedHook`](#memberupdatedhook) | `MEMBER_UPDATED` | 3 |
| [`MemberReturnedHook`](#memberreturnedhook) | `MEMBER_RETURNED` (not in the SDL) | 2 |
| [`MemberRemovedFromPlanHook`](#memberremovedfromplanhook) | `MEMBER_REMOVED_FROM_PLAN` | 2 |
| [`MemberTagAddedHook`](#membertagaddedhook) | `MEMBER_TAG_ADDED` | 1 |
| [`MemberTagRemovedHook`](#membertagremovedhook) | `MEMBER_TAG_REMOVED` | 3 |
| [`MemberCourseProgressStartedHook`](#membercourseprogressstartedhook) | `MEMBER_COURSE_PROGRESS_STARTED` | 11 |
| [`MemberCourseProgressUpdatedHook`](#membercourseprogressupdatedhook) | `MEMBER_COURSE_PROGRESS_UPDATED` | 7 |
| [`MemberCourseProgressCompletedHook`](#membercourseprogresscompletedhook) | `MEMBER_COURSE_PROGRESS_COMPLETED` | 10 |
| [`MemberLessonCompletedHook`](#memberlessoncompletedhook) | `MEMBER_LESSON_COMPLETED` | 7 |
| [`CommentCreatedHook`](#commentcreatedhook) | `COMMENT_CREATED` | 2 |
| [`ReactionCreatedHook`](#reactioncreatedhook) | `REACTION_CREATED` | 1 |
| [`ReactionDeletedHook`](#reactiondeletedhook) | `REACTION_DELETED` | 1 |

## Examples

### MemberJoinedHook

- `space_id` was always present. For a join to the **network** it equals `network_id`; otherwise it is a space.
- Joining the network also sent one message for each space (12 here), within about a second and not in a fixed order.
- There is no `space` object, so the space is not named.

#### Joining the network

```json
{
  "event_id": "553e52b82d6a93992567509e0b5e652f085e92c249bb674f1632fe2419438383",
  "event_timestamp": "2026-01-15T09:20:32.810792+00:00",
  "event_type": "MemberJoinedHook",
  "payload": {
    "member": {
      "id": 90003974,
      "created_at": "2026-01-15T09:20:25Z",
      "updated_at": "2026-01-15T09:20:32Z",
      "email": "",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90003974",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:20:25Z"
    },
    "space_id": 90000823,
    "network_id": 90000823
  }
}
```

#### Joining a space

```json
{
  "event_id": "80e5dcd8ce544ba08e1d6b8cf56a04bfb8a8fb9eed90465a610a0f23a74a244a",
  "event_timestamp": "2026-01-15T09:20:32.901724+00:00",
  "event_type": "MemberJoinedHook",
  "payload": {
    "member": {
      "id": 90003974,
      "created_at": "2026-01-15T09:20:25Z",
      "updated_at": "2026-01-15T09:20:32Z",
      "email": "",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90003974",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:20:25Z"
    },
    "space_id": 90001919,
    "network_id": 90000823
  }
}
```

### MemberLeftHook

- The same `space_id` rule as joins, plus a `reason`. Values seen: `removed` and `deleted`.
- Removing someone from the network sent the network message and 12 space messages over about 3 seconds, mixed with plan messages. The network message was the first departure.
- Someone deleted and added again came back with a **new member id**.

#### Removed from the network

```json
{
  "event_id": "09066d2b7530f38a4ee147492ee69fa04ca9e1ffbcce199c1234917cfef40a9c",
  "event_timestamp": "2026-01-15T09:10:45.509369+00:00",
  "event_type": "MemberLeftHook",
  "payload": {
    "member": {
      "id": 90000001,
      "created_at": "2026-01-15T09:01:02Z",
      "updated_at": "2026-01-15T09:08:51Z",
      "email": "",
      "member_type": "limited",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90000001",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:07:34Z"
    },
    "space_id": 90000823,
    "network_id": 90000823,
    "reason": "removed"
  }
}
```

#### Removed from a space

```json
{
  "event_id": "d0216bcee854a5b8fefb9bc88dca3b5417917a12d6453a7c251f58eb82833778",
  "event_timestamp": "2026-01-15T09:10:45.898707+00:00",
  "event_type": "MemberLeftHook",
  "payload": {
    "member": {
      "id": 90000001,
      "created_at": "2026-01-15T09:01:02Z",
      "updated_at": "2026-01-15T09:08:51Z",
      "email": "",
      "member_type": "limited",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90000001",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:07:34Z"
    },
    "space_id": 90001919,
    "network_id": 90000823,
    "reason": "removed"
  }
}
```

#### Deleted from the network

```json
{
  "event_id": "33cae3b63847e868fd339eb342997d912fd0fd70071d63398e92f269c386bfb9",
  "event_timestamp": "2026-01-15T09:18:01.165961+00:00",
  "event_type": "MemberLeftHook",
  "payload": {
    "member": {
      "id": 90000001,
      "created_at": "2026-01-15T09:01:02Z",
      "updated_at": "2026-01-15T09:17:43Z",
      "email": "",
      "member_type": "limited",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90000001",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:17:42Z"
    },
    "space_id": 90000823,
    "network_id": 90000823,
    "reason": "deleted"
  }
}
```

### MemberUpdatedHook

- `space_id` is `null`.
- It does not say what changed.

#### Profile with little filled in

```json
{
  "event_id": "600ce9dc9d8525124f7e8693f54196cecd27036cd1cfb14d50737f69db2b97e9",
  "event_timestamp": "2026-01-15T09:20:35.592982+00:00",
  "event_type": "MemberUpdatedHook",
  "payload": {
    "member": {
      "id": 90003974,
      "created_at": "2026-01-15T09:20:25Z",
      "updated_at": "2026-01-15T09:20:35Z",
      "email": "",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90003974",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:20:25Z"
    },
    "space_id": null,
    "network_id": 90000823
  }
}
```

#### Profile with an email, bio and photo

```json
{
  "event_id": "3a3b35e28ce04b52238cc76bf947d8b0ff03eb5265aa6f418c9396c46ecc0289",
  "event_timestamp": "2026-01-15T09:27:56.509984+00:00",
  "event_type": "MemberUpdatedHook",
  "payload": {
    "member": {
      "id": 90004933,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2026-01-15T09:27:56Z",
      "email": "member@example.com",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": "Example bio.",
      "referral_count": 0,
      "avatar": "https://media.example.com/avatar.jpg",
      "categories": null,
      "permalink": "https://members.example.com/members/90004933",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:27:52Z"
    },
    "space_id": null,
    "network_id": 90000823
  }
}
```

### MemberReturnedHook

- Sent when a member visits again after some time away (about 8 days in both samples).
- `MEMBER_RETURNED` is not in the GraphQL SDL the other `WebhookEventType` cases come from.

**A member comes back**

```json
{
  "event_id": "cf78fd187ca005b9935a341aa80c9e725d517a061ab1ea474db9bb22dd2f9497",
  "event_timestamp": "2026-01-15T09:27:52.923347+00:00",
  "event_type": "MemberReturnedHook",
  "payload": {
    "member": {
      "id": 90004933,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2026-01-15T09:27:52Z",
      "email": "member@example.com",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": "Example bio.",
      "referral_count": 0,
      "avatar": "https://media.example.com/avatar.jpg",
      "categories": null,
      "permalink": "https://members.example.com/members/90004933",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:27:52Z"
    },
    "network_id": 90000823,
    "previous_last_visited_at": "2026-01-07T17:04:24Z",
    "returned_at": "2026-01-15T09:27:52Z"
  }
}
```

### MemberRemovedFromPlanHook

- The member's fields appear twice: inside `member`, and again at the top level with `member_id`. The two can differ (`location` and `ambassador_level` here).

**Removed from a free plan**

```json
{
  "event_id": "900030cf649b0107beb2acb48242e77e7c93107a2b6f5a8c3768bef26acb8b75",
  "event_timestamp": "2026-01-15T09:10:45.375654+00:00",
  "event_type": "MemberRemovedFromPlanHook",
  "payload": {
    "member": {
      "id": 90000001,
      "created_at": "2026-01-15T09:01:02Z",
      "updated_at": "2026-01-15T09:08:51Z",
      "email": "",
      "member_type": "limited",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90000001",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:07:34Z"
    },
    "member_id": 90000001,
    "created_at": "2026-01-15T09:01:02Z",
    "updated_at": "2026-01-15T09:08:51Z",
    "email": "",
    "first_name": "Test",
    "last_name": "Member",
    "time_zone": "America/New_York",
    "location": "Example City, Example Country",
    "referral_count": 0,
    "avatar": null,
    "categories": null,
    "permalink": "https://members.example.com/members/90000001",
    "ambassador_level": null,
    "plan": {
      "id": 90001371,
      "name": "Plan A",
      "amount": 0,
      "currency": "usd",
      "interval": "month",
      "type": "free",
      "has_free_trial": false
    },
    "subscription": {
      "id": 90001508,
      "current_period_start": "2026-01-15T09:01:04Z",
      "current_period_end": "2026-01-15T09:10:44Z",
      "payment_platform": null,
      "canceled_at": null,
      "trial_length": null,
      "trial_start": null,
      "trial_end": null,
      "purchased_at": "2026-01-15T09:01:04Z"
    }
  }
}
```

### MemberTagAddedHook

- Carries `network_id` and no `space_id`.

**Tag added**

```json
{
  "event_id": "2450f66e978c5d557693ff756805438fe044aef64e2262f9ae6b619237ff02d6",
  "event_timestamp": "2026-01-15T09:15:23.986297+00:00",
  "event_type": "MemberTagAddedHook",
  "payload": {
    "member": {
      "id": 90003015,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2026-01-15T09:15:23Z",
      "email": "",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90003015",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:15:15Z"
    },
    "tag": {
      "id": 90003152,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2025-06-01T12:00:00Z",
      "title": "Tag A",
      "description": null,
      "color": null,
      "custom_field_id": 90003289
    },
    "network_id": 90000823
  }
}
```

### MemberTagRemovedHook

- The same shape as a tag being added.

**Tag removed**

```json
{
  "event_id": "0c735ae7065ac70670dfa2b980fbb242857f21219f9f022a2a51a2c64e3fcfab",
  "event_timestamp": "2026-01-15T09:15:43.530030+00:00",
  "event_type": "MemberTagRemovedHook",
  "payload": {
    "member": {
      "id": 90003015,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2026-01-15T09:15:43Z",
      "email": "",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90003015",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:15:15Z"
    },
    "tag": {
      "id": 90003426,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2025-06-01T12:00:00Z",
      "title": "Tag B",
      "description": null,
      "color": null,
      "custom_field_id": 90003289
    },
    "network_id": 90000823
  }
}
```

### MemberCourseProgressStartedHook

- `progress_percent` is `0`.
- Sent again each time, it seems, the course is opened before any progress is made, with a new `event_id`.

**A course is started**

```json
{
  "event_id": "fcc7bca4f3d66b2051fca3d5aecfe1d8d23012316dcfd1aa8bf1b5a55cb9e22d",
  "event_timestamp": "2026-01-15T09:27:36.986998+00:00",
  "event_type": "MemberCourseProgressStartedHook",
  "payload": {
    "member": {
      "id": 90003974,
      "created_at": "2026-01-15T09:20:25Z",
      "updated_at": "2026-01-15T09:27:36Z",
      "email": "",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90003974",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:25:26Z"
    },
    "course": {
      "id": 90000412,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2026-01-15T09:20:32Z",
      "name": "Course B",
      "collection_id": 90000549
    },
    "progress_percent": 0
  }
}
```

### MemberCourseProgressUpdatedHook

- `progress_percent` is a whole number from 0 to 100. `1` means 1%.
- Sent when a lesson is completed, and again with the same percentage and a new `event_id` with no new progress, apparently when the course is opened.

**Partway through a course**

```json
{
  "event_id": "204fc1297947de348e4596ca168d5010825116506d7761652a13573a93d9bc9f",
  "event_timestamp": "2026-01-15T09:27:39.397849+00:00",
  "event_type": "MemberCourseProgressUpdatedHook",
  "payload": {
    "member": {
      "id": 90003974,
      "created_at": "2026-01-15T09:20:25Z",
      "updated_at": "2026-01-15T09:27:39Z",
      "email": "",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90003974",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:25:26Z"
    },
    "course": {
      "id": 90000412,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2026-01-15T09:20:32Z",
      "name": "Course B",
      "collection_id": 90000549
    },
    "progress_percent": 4
  }
}
```

### MemberCourseProgressCompletedHook

- `progress_percent` is `100`.
- Also sent again with a new `event_id`: 8 times in 20 seconds in one capture.

**A course is completed**

```json
{
  "event_id": "9336b2daa5211f398ad3b075c461125c7929112c8146d5968c051f959c997c33",
  "event_timestamp": "2026-01-15T09:25:14.954656+00:00",
  "event_type": "MemberCourseProgressCompletedHook",
  "payload": {
    "member": {
      "id": 90003974,
      "created_at": "2026-01-15T09:20:25Z",
      "updated_at": "2026-01-15T09:25:14Z",
      "email": "",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90003974",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:20:25Z"
    },
    "course": {
      "id": 90000960,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2026-01-15T09:20:32Z",
      "name": "Course D",
      "collection_id": 90000275
    },
    "progress_percent": 100
  }
}
```

### MemberLessonCompletedHook

- The course is nested as `course: {id, name}`; there is no flat `course_id`. `lesson.space_id` equals `course.id`.
- `lesson.description` is the HTML of the whole lesson, so it can be long.
- No totals are sent ("3 of 12 lessons").

**A lesson is completed**

```json
{
  "event_id": "633bcc388f9d137972d574e5d686a157651e0f2cda729d9b26268dc66de7c8d3",
  "event_timestamp": "2026-01-15T09:27:39.385176+00:00",
  "event_type": "MemberLessonCompletedHook",
  "payload": {
    "member": {
      "id": 90003974,
      "created_at": "2026-01-15T09:20:25Z",
      "updated_at": "2026-01-15T09:27:39Z",
      "email": "",
      "member_type": "full",
      "first_name": "Test",
      "last_name": "Member",
      "time_zone": "America/New_York",
      "location": null,
      "bio": null,
      "referral_count": 0,
      "avatar": null,
      "categories": null,
      "permalink": "https://members.example.com/members/90003974",
      "ambassador_level": "N/A",
      "last_visited_at": "2026-01-15T09:25:26Z"
    },
    "course": {
      "id": 90000412,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2026-01-15T09:20:32Z",
      "name": "Course B",
      "collection_id": 90000549
    },
    "lesson": {
      "id": 90004796,
      "created_at": "2025-06-01T12:00:00Z",
      "updated_at": "2025-06-01T12:00:00Z",
      "space_id": 90000412,
      "type": "lesson",
      "parent_id": 90004522,
      "parent_type": "section",
      "title": "Lesson C",
      "description": "<p dir=\"auto\">Example description.</p>",
      "status": "posted",
      "position": 1,
      "completion_criteria": "button",
      "unlocking_criteria": "none",
      "children_count": 0,
      "permalink": "https://members.example.com/posts/example-lesson"
    },
    "completed_at": "2026-01-15T09:27:38Z"
  }
}
```

### CommentCreatedHook

- Flat: no `member`, `comment` or `post` object. The author is `author_id`.
- `targetable_type` is `Post` for a comment on a lesson as well as on a post. A lesson's id is its post id.
- There is no `network_id`.

**A comment on a lesson**

```json
{
  "event_id": "11e057cd5eee0c05cf6b1af0addc11e5817244e47c2f77e940db3633038f6a5b",
  "event_timestamp": "2026-01-15T09:04:26.593696+00:00",
  "event_type": "CommentCreatedHook",
  "payload": {
    "id": 90001097,
    "created_at": "2026-01-15T09:04:26Z",
    "updated_at": "2026-01-15T09:04:26Z",
    "targetable_id": 90001234,
    "targetable_type": "Post",
    "text": "<p dir=\"auto\">Example comment.</p>",
    "replyable": true,
    "depth": 1,
    "cheer_count": 0,
    "reply_count": 0,
    "reply_to_id": null,
    "author_id": 90000001,
    "space_id": 90000960,
    "files": [],
    "embedded_link": null,
    "permalink": "https://members.example.com/posts/90001234/comments/90001097"
  }
}
```

### ReactionCreatedHook

- Flat. The member is `member_id`.

**A reaction to a post**

```json
{
  "event_id": "2bbd23c54510c006d160d12ecf162fbc095858274a74eabe90ebb577669ee706",
  "event_timestamp": "2026-01-15T09:20:52.822956+00:00",
  "event_type": "ReactionCreatedHook",
  "payload": {
    "id": 90004111,
    "created_at": "2026-01-15T09:20:52Z",
    "updated_at": "2026-01-15T09:20:52Z",
    "space_id": 90002330,
    "network_id": 90000823,
    "targetable_id": 90004248,
    "targetable_type": "Post",
    "targetable_space_id": 90002330,
    "emoji": "❤️",
    "base_emoji": "❤️",
    "member_id": 90003974
  }
}
```

### ReactionDeletedHook

- The same shape and reaction `id` as when it was created.

**A reaction is removed**

```json
{
  "event_id": "5b2c67f4cf51d891c7c4e6ce82f3c50a1d75dfd02d30e1b80dc8bbe171b51b54",
  "event_timestamp": "2026-01-15T09:22:02.971954+00:00",
  "event_type": "ReactionDeletedHook",
  "payload": {
    "id": 90004111,
    "created_at": "2026-01-15T09:20:52Z",
    "updated_at": "2026-01-15T09:22:02Z",
    "space_id": 90002330,
    "network_id": 90000823,
    "targetable_id": 90004248,
    "targetable_type": "Post",
    "targetable_space_id": 90002330,
    "emoji": "❤️",
    "base_emoji": "❤️",
    "member_id": 90003974
  }
}
```

## Order and repeats

Listed by `event_timestamp`. Messages did not always arrive in that order.

### Two lessons of a course

One member, one course. The repeated 4% came with no lesson completed in between.

| After | `event_type` | Detail |
|---|---|---|
| 0.0 s | `MemberCourseProgressStartedHook` | `progress_percent` 0 |
| 2.4 s | `MemberLessonCompletedHook` | one lesson |
| 2.4 s | `MemberCourseProgressUpdatedHook` | `progress_percent` 4 |
| 14.2 s | `MemberCourseProgressUpdatedHook` | `progress_percent` 4 |
| 16.5 s | `MemberLessonCompletedHook` | one lesson |
| 16.6 s | `MemberCourseProgressUpdatedHook` | `progress_percent` 8 |

### A one-lesson course finished, then opened again

Every `completed` message had its own `event_id`.

| After | `event_type` | Detail |
|---|---|---|
| 0.0 s | `MemberCourseProgressStartedHook` | `progress_percent` 0 |
| 1.8 s | `MemberLessonCompletedHook` | one lesson |
| 1.8 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |
| 6.2 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |
| 10.7 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |
| 13.6 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |
| 15.8 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |
| 17.6 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |
| 19.5 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |
| 21.2 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |

### The last two lessons of a longer course

There is no `updated` message at 100%.

| After | `event_type` | Detail |
|---|---|---|
| 0.0 s | `MemberLessonCompletedHook` | one lesson |
| 0.0 s | `MemberCourseProgressUpdatedHook` | `progress_percent` 96 |
| 2.1 s | `MemberCourseProgressUpdatedHook` | `progress_percent` 96 |
| 3.7 s | `MemberLessonCompletedHook` | one lesson |
| 3.7 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |
| 7.6 s | `MemberCourseProgressCompletedHook` | `progress_percent` 100 |

### Removed from the network

| After | `event_type` | Detail |
|---|---|---|
| 0.0 s | `MemberRemovedFromPlanHook` | a plan |
| 0.1 s | `MemberLeftHook` | the network |
| 0.4 s | `MemberRemovedFromPlanHook` | a plan |
| 0.5 s | `MemberLeftHook` | a space |
| 0.8 s | `MemberLeftHook` | a space |
| 1.0 s | `MemberLeftHook` | a space |
| 1.2 s | `MemberLeftHook` | a space |
| 1.6 s | `MemberLeftHook` | a space |
| 2.0 s | `MemberLeftHook` | a space |
| 2.2 s | `MemberLeftHook` | a space |
| 2.4 s | `MemberLeftHook` | a space |
| 2.6 s | `MemberLeftHook` | a space |
| 2.8 s | `MemberLeftHook` | a space |
| 3.0 s | `MemberLeftHook` | a space |
| 3.3 s | `MemberLeftHook` | a space |

### Joining the network

| After | `event_type` | Detail |
|---|---|---|
| 0.0 s | `MemberJoinedHook` | the network |
| 0.1 s | `MemberJoinedHook` | a space |
| 0.1 s | `MemberJoinedHook` | a space |
| 0.2 s | `MemberJoinedHook` | a space |
| 0.3 s | `MemberJoinedHook` | a space |
| 0.4 s | `MemberJoinedHook` | a space |
| 0.4 s | `MemberJoinedHook` | a space |
| 0.5 s | `MemberJoinedHook` | a space |
| 0.7 s | `MemberJoinedHook` | a space |
| 0.8 s | `MemberJoinedHook` | a space |
| 0.9 s | `MemberJoinedHook` | a space |
| 0.9 s | `MemberJoinedHook` | a space |
| 1.0 s | `MemberJoinedHook` | a space |
| 2.8 s | `MemberUpdatedHook` | `space_id` is `null` |

## Not captured

Posts, articles, questions, polls, RSVPs, events, custom field responses, purchases and other plan changes, badges, invitations, join requests, and a member leaving by themselves.
