# UNA 14 Callable API Service Surface — `/api.php?r=module/method[/class]`

## 1. Mechanism
`api.php`: `bx_api_check_access()` (requires `sys_api_enable`; optional Bearer key via `sys_api_access_by_key` or Origin via `sys_api_access_by_origin`) → parse `r=module/method/class` (class defaults `Module`) → `BxDolRequest::serviceExists()` → safe/public gate → `BxDolService::call($sModule,$sMethod,$aParams,$sClass)`.
- Request method `foo_bar` → PHP `serviceFooBar` (via `bx_gen_method_name`) on class `{prefix}{sClass}`. `sModule=system`+`Module` → forced to `BaseServices` (`BxTemplServices`/`BxBaseServices`).
- Gate (api.php:53-70): if `sys_api_access_unsafe_services` OFF → callable only if `is_safe_service` OR `is_public_service`. If ON → ALL `service*` callable.
- Impl `inc/classes/BxDolModule.php:169-191` (mirror `template/scripts/BxBaseServices.php:20-42`): `serviceIsSafeService` checks key in `serviceGetSafeServices()`; `serviceIsPublicService` checks `serviceGetPublicServices()`. Base returns `array()`. Modules override with `array_merge(parent,[...])`.
- public = callable while logged-out (account creation / password reset). Same allow-list governs OAuth2 (`BxOAuthAPI.php:228-236`) and template macros.

## 2. System module (`system` → BxBaseServices) — core sensitive surface
**Public (logged-out):** `get_products_names`, `get_page_by_request`.
**Safe:** `get_menu`, `get_create_post_form`, `keyword_search`, `get_data_search_api`, `cmts`, `get_footer`, `set_badges`, `get_page_block_data`, `set_page_block_data`, `get_url_info`, `create_account_form`, `account_settings_email/password/del_account/info`, `forgot_password`, `switch_profile`, `account_profile_switcher`, `email_confirmation`, `confirm_email`, `categories_list`, `member_auth_code`, `login_form`, `login_form_only`, `logout`, `test`, `keywords_cloud`, `profile_membership/notifications/info/counters`, `get_count_online_profiles`, `browse_recommendations_friends/subscriptions`, `browse_friends`, `set_membership`, `browse_friend_requests/requested/subscribed_me/subscriptions/members`, `update_settings`, `befriend`, charts `get_chart_growth/stats`, `get_data_by_interval`, `get_cart_items_count`, `get_orders_count`, `do`/`get_performed_by`, `perform`, `get_data_api`, `get_stat_block`, `perfom_action_api`, `get_labels`, `get_form`/`get_results`.
NB: `module_install/uninstall/enable/disable/delete/update` exist but are NOT safe (only via unsafe flag).

## 3. Per-module safe/public declarations
Modules extending `BxBaseModGeneralModule`/`Text` inherit base general safe list; modules extending `BxDolModule` inherit EMPTY (nothing safe).

**Base classes (`modules/base/*`):**
- `BxBaseModGeneralModule`: `module_icon`, `get_link`, `get_search_result_unit`, `browse`, `browse_featured`, `browse_favorite`, `get_create_post_form`, `entity_create`, `entity_edit`, `entity_delete`, `update_image`, `entity_text_block`, `entity_info`, `entity_info_full/extended`, `entity_location`, `entity_comments`, `entity_attachments`, `categories_multi_list`, `entity_all_actions`, `entity_actions`, `entity_social_sharing`, `my_entries_actions`, `get_profiles`.
- `BxBaseModTextModule`: + `get_block_poll_answers/results`, `get_menu_addon_manage_tools_profile_stats`, `browse_public/popular/top/updated/author`, `categories_multi_list_context`.
- `BxBaseModProfileModule`: + `profile_unit_safe`, `profile_url`, `browse_recommended`, `browse_recent/active/top/online_profiles`, `browse_connections`, `browse_connections_everywhere`, `browse_by_acl`.
- `BxBaseModGroupsModule`: + `get_questionnaire`, `get_initial_members`, `entity_invite`.
- Connect / Notifications / Payment / Template base modules override to `array()`.

**Boonex highlights:**
- `bx_api`: safe `delete_page`, `change_account_password`, `switch_profile`; public `test`, `get_page`, `reset_password_send_request`, `reset_password_check_code`, `create_account`.
- `bx_payment`: `get_block_join/carts/cart/cart_history/list_my/history`, `get_provider_options`, `initialize_checkout_api`, `stripe_v3_create_session_api`.
- `bx_credits`: `get_block_bundles/orders/history`.
- `bx_market`: + `entity_download`, `entity_author_entities`, `block_licenses`.
- `bx_albums`: + `entity_add_files`, `media_comments`, `browse_recent/featured/popular/top/favorite_media`.
- `bx_channels`/`bx_spaces`: entity_breadcrumb/parent/childs, search_result_by_hashtag; spaces + browse_top_level.
- `bx_timeline`: `get_create_post_form`, `get_search_result_unit`, `get_block_post*`, `get_block_view*`, `get_posts`, `serviceGetRepostElementBlockApi`.
- `bx_messenger`: `find_convo`, `leave_convo`, `delete_convo`, `get_convo`, `get_convo_url`, `get_convos_list`, `get_convo_messages`, `get_send_form`, `remove_jot`, `search_users`, `search_lots`, `get_parts_list`, `save_parts_list`, `get_block_contacts`, `get_convo_message/item`, `clear_ghost`.
- `bx_notifications`: `get_block_view`, `get_data`, `get_unread_notifications_num`, `mark_as_read`, `enable_setting`, `change_setting`.
- `bx_invites`: `get_block_invite/form_invite/form_request`, `get_link`.
- `bx_forum`/`bx_courses`: `browse_new/latest/top/popular/updated/partaken/index`, `search`; courses + `hide`, `publish`, `pass_node`, `pass_data`.
- `bx_elasticsearch`: `search_simple`, `search_extended`.
- `bx_wiki`: `contents`, `missing_translations`, `outdated_translations`. `bx_quoteofday`: `get_quote`. `bx_glossary`: `browse_alphabetical`.
- `modzzz/*` (goal/jobs/listing): `entity_reviews`, `entity_reviews_rating`, `categories_list`, `browse_category` (jobs + browse_local/remote).

**Third-party — NO override (inherit parent):** `msolutions/fansonly`, `aqb/*` inherit base lists. **`smsoftwares/people`, `gfunnel/onboarding_module`, `publicchat/complete_profile` extend `BxDolModule` → EMPTY safe list ⇒ their `service*` NOT callable unless unsafe flag ON.**

## 4. Size of unsafe surface (`grep -c 'function service'`)
base≈306, boonex≈1241, template/scripts≈261, modzzz≈211, aqb≈31, msolutions≈23, smsoftwares≈4, gfunnel≈1, publicchat≈1. Top: timeline 97, albums 75, courses 73, base/general 93, base/profile 87, base/groups 69, messenger 66, credits 63, payment 48, market 34. **~2000 methods gated behind the single `sys_api_access_unsafe_services` flag; only a few hundred safe/public by default.**

## 5. `Api`-suffix REST methods (decoupled UNA frontend)
No `serviceApi*` prefix; REST methods use `Api` **suffix**, wired through the same safe allow-list. Examples: `system/get_data_search_api`, `BxBaseCmtsServices::serviceGetDataApi/GetCommentsApi`, `BxBaseUploaderServices::serviceGetDataApi`, `servicePerfomActionApi` (grid), `bx_payment` `serviceInitializeCheckoutApi`/`serviceStripeV3CreateSessionApi`, `bx_timeline` `serviceGetRepostElementBlockApi`, `bx_stripe_connect` `serviceGetOptionsApiScope`. They branch on `bx_is_api()` and return `bx_api_get_block(...)` structures. `system/get_page`/`get_page_by_request` renders whole pages as JSON for the app.
