<?php
require_once 'config.php';

/**
 * Community & Tree Planting Module
 * - Citizen contributions
 * - Tree planting proposals
 * - Green area tracking
 * - Gamification (points, badges)
 */

class CommunityModule {

    // Add new tree planting proposal
    public static function addTreeProposal($data) {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO tree_proposals
            (user_name, user_email, latitude, longitude, location_description,
             tree_count, species, reason, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        $stmt->execute([
            $data['user_name'],
            $data['user_email'],
            $data['latitude'],
            $data['longitude'],
            $data['location_description'],
            $data['tree_count'],
            $data['species'],
            $data['reason']
        ]);

        $proposalId = $db->lastInsertId();

        // Award points
        self::awardPoints($data['user_email'], 10, 'tree_proposal');

        return [
            'success' => true,
            'proposal_id' => $proposalId,
            'message' => 'Ağaç dikim öneriniz alındı! Belediye değerlendirmesi bekliyor.',
            'points_earned' => 10
        ];
    }

    // Get all tree proposals
    public static function getTreeProposals($filters = []) {
        $db = getDB();

        $query = "SELECT * FROM tree_proposals WHERE 1=1";
        $params = [];

        if (isset($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['city_id'])) {
            // Get city bounds
            $city = $db->prepare("SELECT * FROM cities WHERE id = ?");
            $city->execute([$filters['city_id']]);
            $cityData = $city->fetch();

            if ($cityData) {
                // Rough bounds (±0.1 degrees ~ 10km)
                $query .= " AND latitude BETWEEN ? AND ? AND longitude BETWEEN ? AND ?";
                $params[] = $cityData['latitude'] - 0.1;
                $params[] = $cityData['latitude'] + 0.1;
                $params[] = $cityData['longitude'] - 0.1;
                $params[] = $cityData['longitude'] + 0.1;
            }
        }

        $query .= " ORDER BY created_at DESC LIMIT 100";

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // Vote for a tree proposal
    public static function voteProposal($proposalId, $userEmail, $vote) {
        $db = getDB();

        // Check if already voted
        $check = $db->prepare("SELECT * FROM proposal_votes WHERE proposal_id = ? AND user_email = ?");
        $check->execute([$proposalId, $userEmail]);

        if ($check->fetch()) {
            return ['success' => false, 'message' => 'Bu öneri için zaten oy kullandınız'];
        }

        // Add vote
        $stmt = $db->prepare("INSERT INTO proposal_votes (proposal_id, user_email, vote, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$proposalId, $userEmail, $vote]);

        // Update proposal vote count
        $update = $db->prepare("UPDATE tree_proposals SET votes = votes + ? WHERE id = ?");
        $update->execute([$vote === 'up' ? 1 : -1, $proposalId]);

        // Award points
        self::awardPoints($userEmail, 2, 'vote');

        return [
            'success' => true,
            'message' => 'Oyunuz kaydedildi',
            'points_earned' => 2
        ];
    }

    // Report environmental issue
    public static function reportIssue($data) {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO environmental_reports
            (user_name, user_email, latitude, longitude, issue_type, description, photo_url, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())
        ");

        $stmt->execute([
            $data['user_name'],
            $data['user_email'],
            $data['latitude'],
            $data['longitude'],
            $data['issue_type'],
            $data['description'],
            $data['photo_url'] ?? null
        ]);

        $reportId = $db->lastInsertId();

        // Award points based on issue type
        $points = $data['issue_type'] === 'urgent' ? 15 : 10;
        self::awardPoints($data['user_email'], $points, 'environmental_report');

        return [
            'success' => true,
            'report_id' => $reportId,
            'message' => 'Raporunuz alındı. Yerel yönetim bilgilendirildi.',
            'points_earned' => $points
        ];
    }

    // Get environmental reports
    public static function getReports($filters = []) {
        $db = getDB();

        $query = "SELECT * FROM environmental_reports WHERE 1=1";
        $params = [];

        if (isset($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['issue_type'])) {
            $query .= " AND issue_type = ?";
            $params[] = $filters['issue_type'];
        }

        $query .= " ORDER BY created_at DESC LIMIT 100";

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // Award points to user
    private static function awardPoints($userEmail, $points, $action) {
        $db = getDB();

        // Check if user exists
        $check = $db->prepare("SELECT * FROM community_users WHERE email = ?");
        $check->execute([$userEmail]);
        $user = $check->fetch();

        if ($user) {
            // Update points
            $stmt = $db->prepare("UPDATE community_users SET points = points + ?, total_contributions = total_contributions + 1 WHERE email = ?");
            $stmt->execute([$points, $userEmail]);
        } else {
            // Create new user
            $stmt = $db->prepare("INSERT INTO community_users (email, points, total_contributions) VALUES (?, ?, 1)");
            $stmt->execute([$userEmail, $points]);
        }

        // Log activity
        $log = $db->prepare("INSERT INTO user_activities (user_email, action, points, created_at) VALUES (?, ?, ?, NOW())");
        $log->execute([$userEmail, $action, $points]);

        // Check for badge awards
        self::checkBadges($userEmail);
    }

    // Check and award badges
    private static function checkBadges($userEmail) {
        $db = getDB();

        $user = $db->prepare("SELECT * FROM community_users WHERE email = ?");
        $user->execute([$userEmail]);
        $userData = $user->fetch();

        if (!$userData) return;

        $badges = [];

        // Contribution count badges
        if ($userData['total_contributions'] >= 100 && !self::hasBadge($userEmail, 'super_contributor')) {
            $badges[] = ['badge' => 'super_contributor', 'name' => 'Süper Katkıcı', 'description' => '100+ katkı'];
        } elseif ($userData['total_contributions'] >= 50 && !self::hasBadge($userEmail, 'active_contributor')) {
            $badges[] = ['badge' => 'active_contributor', 'name' => 'Aktif Katkıcı', 'description' => '50+ katkı'];
        } elseif ($userData['total_contributions'] >= 10 && !self::hasBadge($userEmail, 'contributor')) {
            $badges[] = ['badge' => 'contributor', 'name' => 'Katkıcı', 'description' => '10+ katkı'];
        }

        // Points badges
        if ($userData['points'] >= 500 && !self::hasBadge($userEmail, 'green_champion')) {
            $badges[] = ['badge' => 'green_champion', 'name' => 'Yeşil Şampiyon', 'description' => '500+ puan'];
        } elseif ($userData['points'] >= 200 && !self::hasBadge($userEmail, 'eco_warrior')) {
            $badges[] = ['badge' => 'eco_warrior', 'name' => 'Eko Savaşçı', 'description' => '200+ puan'];
        }

        // Award new badges
        foreach ($badges as $badge) {
            $stmt = $db->prepare("INSERT INTO user_badges (user_email, badge_id, earned_at) VALUES (?, ?, NOW())");
            $stmt->execute([$userEmail, $badge['badge']]);
        }

        return $badges;
    }

    private static function hasBadge($userEmail, $badgeId) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM user_badges WHERE user_email = ? AND badge_id = ?");
        $stmt->execute([$userEmail, $badgeId]);
        return $stmt->fetch() !== false;
    }

    // Get user profile
    public static function getUserProfile($userEmail) {
        $db = getDB();

        $user = $db->prepare("SELECT * FROM community_users WHERE email = ?");
        $user->execute([$userEmail]);
        $userData = $user->fetch();

        if (!$userData) {
            return null;
        }

        // Get badges
        $badges = $db->prepare("SELECT * FROM user_badges WHERE user_email = ? ORDER BY earned_at DESC");
        $badges->execute([$userEmail]);
        $badgeList = $badges->fetchAll();

        // Get recent activities
        $activities = $db->prepare("SELECT * FROM user_activities WHERE user_email = ? ORDER BY created_at DESC LIMIT 10");
        $activities->execute([$userEmail]);
        $activityList = $activities->fetchAll();

        return [
            'user' => $userData,
            'badges' => $badgeList,
            'activities' => $activityList,
            'rank' => self::getUserRank($userData['points'])
        ];
    }

    private static function getUserRank($points) {
        if ($points >= 500) return ['level' => 5, 'title' => 'Yeşil Şampiyon', 'icon' => '🏆'];
        if ($points >= 200) return ['level' => 4, 'title' => 'Eko Savaşçı', 'icon' => '🌟'];
        if ($points >= 100) return ['level' => 3, 'title' => 'Çevre Dostu', 'icon' => '🌿'];
        if ($points >= 50) return ['level' => 2, 'title' => 'Gönüllü', 'icon' => '🌱'];
        return ['level' => 1, 'title' => 'Yeni Başlayan', 'icon' => '🌾'];
    }

    // Leaderboard
    public static function getLeaderboard($limit = 10, $period = 'all') {
        $db = getDB();

        $query = "SELECT email, points, total_contributions FROM community_users";

        if ($period === 'month') {
            // Points earned this month
            $query = "SELECT u.email, COALESCE(SUM(a.points), 0) as points, COUNT(a.id) as total_contributions
                      FROM community_users u
                      LEFT JOIN user_activities a ON u.email = a.user_email
                      WHERE MONTH(a.created_at) = MONTH(NOW()) AND YEAR(a.created_at) = YEAR(NOW())
                      GROUP BY u.email";
        } elseif ($period === 'week') {
            $query = "SELECT u.email, COALESCE(SUM(a.points), 0) as points, COUNT(a.id) as total_contributions
                      FROM community_users u
                      LEFT JOIN user_activities a ON u.email = a.user_email
                      WHERE WEEK(a.created_at) = WEEK(NOW()) AND YEAR(a.created_at) = YEAR(NOW())
                      GROUP BY u.email";
        }

        $query .= " ORDER BY points DESC LIMIT ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$limit]);

        $leaderboard = $stmt->fetchAll();

        // Add ranks
        foreach ($leaderboard as $index => &$user) {
            $user['rank'] = $index + 1;
            $user['rank_info'] = self::getUserRank($user['points']);
        }

        return $leaderboard;
    }

    // Green area statistics
    public static function getGreenAreaStats($cityId = null) {
        $db = getDB();

        $stats = [
            'total_proposals' => 0,
            'approved_trees' => 0,
            'pending_trees' => 0,
            'total_reports' => 0,
            'resolved_reports' => 0,
            'community_members' => 0
        ];

        // Total proposals
        $query = "SELECT COUNT(*) as count, SUM(tree_count) as trees FROM tree_proposals WHERE status = 'approved'";
        if ($cityId) $query .= " AND city_id = $cityId";

        $result = $db->query($query)->fetch();
        $stats['approved_trees'] = $result['trees'] ?? 0;

        $query = "SELECT COUNT(*) as count, SUM(tree_count) as trees FROM tree_proposals WHERE status = 'pending'";
        if ($cityId) $query .= " AND city_id = $cityId";

        $result = $db->query($query)->fetch();
        $stats['pending_trees'] = $result['trees'] ?? 0;

        // Reports
        $stats['total_reports'] = $db->query("SELECT COUNT(*) as count FROM environmental_reports")->fetch()['count'];
        $stats['resolved_reports'] = $db->query("SELECT COUNT(*) as count FROM environmental_reports WHERE status = 'resolved'")->fetch()['count'];

        // Community members
        $stats['community_members'] = $db->query("SELECT COUNT(*) as count FROM community_users")->fetch()['count'];

        return $stats;
    }
}

// API Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';

    $response = [];

    switch ($action) {
        case 'add-tree-proposal':
            $response = CommunityModule::addTreeProposal($data);
            break;

        case 'vote-proposal':
            $response = CommunityModule::voteProposal($data['proposal_id'], $data['user_email'], $data['vote']);
            break;

        case 'report-issue':
            $response = CommunityModule::reportIssue($data);
            break;

        default:
            $response = ['error' => 'Invalid action'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    $response = [];

    switch ($action) {
        case 'proposals':
            $filters = [];
            if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
            if (isset($_GET['city_id'])) $filters['city_id'] = $_GET['city_id'];

            $response = CommunityModule::getTreeProposals($filters);
            break;

        case 'reports':
            $filters = [];
            if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
            if (isset($_GET['issue_type'])) $filters['issue_type'] = $_GET['issue_type'];

            $response = CommunityModule::getReports($filters);
            break;

        case 'profile':
            $email = $_GET['email'] ?? '';
            $response = CommunityModule::getUserProfile($email);
            break;

        case 'leaderboard':
            $limit = intval($_GET['limit'] ?? 10);
            $period = $_GET['period'] ?? 'all';
            $response = CommunityModule::getLeaderboard($limit, $period);
            break;

        case 'stats':
            $cityId = $_GET['city_id'] ?? null;
            $response = CommunityModule::getGreenAreaStats($cityId);
            break;

        default:
            $response = ['error' => 'Invalid action'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
