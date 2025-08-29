-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 29, 2025 at 08:55 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `employee_evaluation_system`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`speedsri`@`%` PROCEDURE `CalculateOverallScore` (IN `eval_id` INT)   BEGIN
    DECLARE avg_score DECIMAL(3,1);

    SELECT AVG(es.score * COALESCE(c.weight, 1.0) /
              (SELECT SUM(COALESCE(c2.weight, 1.0))
               FROM evaluation_scores es2
               JOIN competencies c2 ON es2.competency_id = c2.competency_id
               WHERE es2.evaluation_id = eval_id))
    INTO avg_score
    FROM evaluation_scores es
    JOIN competencies c ON es.competency_id = c.competency_id
    WHERE es.evaluation_id = eval_id;

    UPDATE evaluations
    SET overall_score = avg_score,
        overall_rating = CASE
            WHEN avg_score >= 9.0 THEN 'exceptional'
            WHEN avg_score >= 7.5 THEN 'exceeds_expectations'
            WHEN avg_score >= 5.0 THEN 'meets_expectations'
            WHEN avg_score >= 3.0 THEN 'needs_improvement'
            ELSE 'unsatisfactory'
        END
    WHERE evaluation_id = eval_id;
END$$

CREATE DEFINER=`speedsri`@`%` PROCEDURE `CreateEvaluationReminders` (IN `period_id` INT)   BEGIN
    DECLARE reminder_days INT DEFAULT 7;

    SELECT setting_value INTO reminder_days
    FROM system_settings
    WHERE setting_key = 'reminder_days_before';

    INSERT INTO notifications (user_id, notification_type, title, message)
    SELECT DISTINCT u.user_id, 'evaluation_due',
           'Evaluation Reminder',
           CONCAT('You have an evaluation due for period ', ep.period_name)
    FROM evaluation_periods ep
    CROSS JOIN employees e
    JOIN users u ON e.user_id = u.user_id
    LEFT JOIN evaluations ev ON e.employee_id = ev.employee_id
        AND ev.period_id = period_id
    WHERE ep.period_id = period_id
        AND (ev.evaluation_id IS NULL OR ev.status IN ('not_started', 'draft'))
        AND DATEDIFF(ep.evaluation_deadline, CURDATE()) = reminder_days;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `audit_id` int NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `user_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `competencies`
--

CREATE TABLE `competencies` (
  `competency_id` int NOT NULL,
  `category_id` int NOT NULL,
  `competency_name` varchar(200) NOT NULL,
  `positive_indicator` text,
  `negative_indicator` text,
  `weight` decimal(5,2) DEFAULT '1.00',
  `is_required` tinyint(1) DEFAULT '0',
  `applies_to_level` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `competencies`
--

INSERT INTO `competencies` (`competency_id`, `category_id`, `competency_name`, `positive_indicator`, `negative_indicator`, `weight`, `is_required`, `applies_to_level`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Striving to beat deadlines', 'Consistently delivers work ahead of schedule', 'Being satisfied if the project is only a little bit late', 1.00, 0, NULL, 1, '2025-08-27 06:28:40', '2025-08-27 06:28:40'),
(2, 1, 'Setting high quality standards', 'Setting your own high standards for quality of work beyond what is normally expected', 'Doing the minimum needed to get by', 1.00, 0, NULL, 1, '2025-08-27 06:28:40', '2025-08-27 06:28:40'),
(3, 1, 'Striving to beat deadlines', 'Consistently delivers work ahead of schedule', 'Being satisfied if the project is only a little bit late', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(4, 1, 'Setting high quality standards', 'Setting your own high standards for quality of work beyond what is normally expected', 'Doing the minimum needed to get by', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(5, 1, 'Seeking better ways to do things', 'Aggressively seeking better ways to do things', 'Accepting the old way of doing things as the best', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(6, 1, 'Improving profitability', 'Improving the profitability of your store or division', 'Being satisfied with middle of the pack performance', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(7, 2, 'Visualizing future possibilities', 'The ability to visualize what might or could be', 'A day to day approach to handling issues and challenges', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(8, 2, 'Identifying unmet customer needs', 'Identifying the unmet needs of existing or potential customers', 'Focusing all your energy on meeting present day needs of customers while neglecting future concerns', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(9, 2, 'Understanding competition', 'Understanding the competition and how their approaches might impact on the organization\'s present policies and procedures', 'Waiting for a competitor to launch a new approach or another problem to occur and then reacting to it', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(10, 2, 'Developing contingency plans', 'Developing contingency plans and having the resources ready to carry them out', 'Focusing on short-term objectives and neglecting the long-term', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(11, 2, 'Aligning with strategic plan', 'Aligning the stores / division\'s goals with the organization\'s strategic plan', 'Concentrate in just meeting your own goals, without considering how you can add to the \"big picture\"', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(12, 3, 'Systematic problem resolution', 'Resolving a problem in a systematic, step by step way', 'Attempting to solve a problem by quickly trying whatever comes to mind', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(13, 3, 'Analyzing causal factors', 'Thinking about the chain of causal factors that led to a problem', 'Explaining problems in a vague, general way, e.g., \"that department never knows what they want\", \"this computer never works right\"', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(14, 3, 'Anticipating outcomes', 'Thinking ahead about the outcome of an action (if I do A, then B and C will also happen)', 'Doing works as it comes without thinking through how it might impact other projects; dealing with problems on the fly', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(15, 3, 'Considering long-term impact', 'Considering the long-term implications of decisions', 'Dealing with situations as they arise without thinking through the longer term impact', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(16, 4, 'Understanding systems and programs', 'Taking the time to get a detailed understanding of the systems and programs relevant to your work', 'Understanding the \"big picture\" and letting someone else figure out the details', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(17, 4, 'Systematic work checking', 'Systematically checking through each element of work you have done to ensure it is correct', 'Getting the job done quickly and being somewhat confident that almost everything is correct', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(18, 4, 'Maintaining organized records', 'Keeping your records and documentation well organized and up to date', 'Keeping track of everything in your head and waiting for a slow period to get the records up to date', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(19, 4, 'Monitoring progress against goals', 'Monitoring your progress against goals and deadlines', 'Doing your work as best you can and letting management worry about deadlines', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(20, 5, 'Providing challenging opportunities', 'Actively seeking out opportunities that will challenge your staff and will enable them to learn new skills', 'Assigning work that you know the person will do well and efficiently because they have done similar assignments many times before', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(21, 5, 'Providing constructive feedback', 'Providing constructive criticism and reassurance to someone after a setback', 'Telling other people what an employee should have done differently but not telling the employee', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(22, 5, 'Giving ongoing feedback', 'Giving on going day to day feedback on a person\'s work', 'Waiting until the performance appraisal management meeting to give feedback', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(23, 5, 'Empowering staff development', 'Empowering your staff to take the lead in developing themselves', 'Keeping tight control over all your staff actions and decisions', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(24, 6, 'Giving clear instructions', 'Giving clear instructions as to what you expect from others', 'Giving assignment without deadlines or with unclear quality requirements', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(25, 6, 'Managing expectations', 'Clearly explaining to customers / co-workers when their expectations are unreasonable or are at odds with the strategic direction of the organization', 'Complaining to others about inappropriate requests from customers / co-workers but not directly confronting them', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(26, 6, 'Confronting performance issues', 'Confronting people when their performance is not up to standard', 'Being reluctant to risk upsetting an employee by telling them when their performance is inadequate', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(27, 7, 'Sharing information with colleagues', 'Spontaneously pointing out helpful information to colleagues', 'Hoarding information, keeping your expertise to yourself', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(28, 7, 'Continuous learning', 'Learning everything possible about your area the systems and procedures as well as a general understanding of the business', 'Learning just enough to get by', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(29, 7, 'Explaining procedures', 'Explaining why a procedure has been set up in a certain way', 'Explaining only the procedure, or only giving the specific steps to solve a problem', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(30, 8, 'Adapting approach', 'Giving up your personally preferred approach to better meet the needs of your store / division', 'Sticking firmly to the way you like to do things regardless of the other parties involved', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(31, 8, 'Adjusting plans', 'Changing your plan when new information shows the original plan is inappropriate, even though the changes may involve extra work or conflict', 'Staying with the original plan, no matter what changes or is learned during the project', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(32, 8, 'Adapting service approach', 'Trying to provide service in a way that is most comfortable for your particular store / division', 'Approaching others at the level at which you are most comfortable', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(33, 9, 'Considering others\' perspectives', 'Considering the point of view of the other people involved in a project so that you will know how to bring them on side', 'Telling it \"like it is\" from your own, or your departments, point of view and blaming the other person if they don\'t respond as you would like', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(34, 9, 'Persistent persuasion', 'When you fail to get someone to do or agree to something, trying different tactics until you succeed', 'Giving up after a single attempted to convince someone (\"They weren\'t interested so I didn\'t even try\")', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(35, 9, 'Strategic communication', 'Thinking through your approach before asking for something or giving direction (what you will say, who you will say it to, when you will say it)', 'Being too busy to worry about the niceties of communication', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(36, 9, 'Considering responses', 'Thinking about how people will respond before you present an argument', 'Thinking exclusively about practical matters and not considering the people you are talking to', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(37, 10, 'Asking probing questions', 'Asking probing questions to be sure you understand exactly what a customer or colleague wants and why and then reiterating it to them to ensure understanding', 'Doing what you were asked to do without asking why or accepting superficial answers to your questions', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(38, 10, 'Resolving data discrepancies', 'Digging to resolve discrepancies in data', 'Going ahead with work based on information that you think is probably correct without asking further questions', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(39, 10, 'Seeking expert information', 'Regularly getting extra information and opinions from people in the industry who have expertise in relevant methods and technologies. Reading manuals, internal documents, technical journals and other information sources to find out as much as possible about your customers and or relevant technologies', 'Counting on the people immediately at hand (your manager, staff or the training department) to keep you informed on relevant technologies or business information. Learning just enough about the system you are working with to deal with the immediate problem', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(40, 11, 'Proactive problem prevention', 'Thinking ahead about possible problems and taking action to prevent or resolve them', 'Continually putting out fires', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(41, 11, 'Anticipating future needs', 'Looking ahead at trends in technology or anticipating changes in the business environment and acting to ensure your group will have the necessary skills needed to deal with future systems', 'Focusing on the immediate concerns of your group', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(42, 11, 'Initiating performance improvement', 'Initiating course of action that will lead to improved performance by the group', 'Hoping that your group will get better over time', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(43, 11, 'Volunteering information', 'Volunteering information or pointing out concerns about a project even if you are not directly involved', 'Keeping your nose out of other people\'s business even if you think they are heading into difficulty', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(44, 11, 'Recognizing unnoticed tasks', 'Recognizing things that should be done that others may be unaware of and bringing them to their attention', 'Doing your job as specified by your manager irrespective of problems you can foresee', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(45, 11, 'Suggesting improvements', 'Suggesting course of action that the group can take to improve performance', 'Feeling that things should be done differently but keeping those feelings to yourself', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(46, 12, 'Trying new approaches', 'Trying new approaches', 'Solving a problem the way its always been done', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(47, 12, 'Reassessing systems', 'Re-assessing systems and procedures to look for better ways of doing things', 'Sticking to established procedures', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(48, 12, 'Implementing suggestions', 'Making suggestions for improving service and seeing them through to implementation', 'Ignoring others suggestions for improvement', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(49, 13, 'Creating discussion opportunities', 'Creating opportunities for meaningful discussion, e.g., inviting an employee or colleague so sit and have a chat in a relaxed environment', 'Quickly getting the facts from someone and rushing on to the next thing', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(50, 13, 'Deferring judgment', 'Deferring judgment on what someone is saying and instead focusing on finding out more', 'Jumping in with a solution when someone is starting to express their concerns', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(51, 13, 'Recognizing non-verbal cues', 'Recognizing when an employee\'s non-verbal behavior (e.g., eye contact and body posture) does not match what he or she is saying. Recognizing underlying concerns or feelings in a co-worker that they may not be exposing', 'Accepting what is said at face value without considering non-verbal clues', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(52, 14, 'Identifying opinion leaders', 'Identifying who the opinion leaders are in the organization and community; knowing whose advice is listened to and whose is ignored', 'Steering clear of trying to deal with the sometimes complex and conflicting needs / points of view of different people and departments', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(53, 14, 'Using informal systems', 'Getting things done by using relationships with others and the \"informal system\"', 'Relying entirely on formal systems to get something done', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(54, 14, 'Understanding organizational context', 'Knowing what major initiatives and projects are going on in the organization at any given time. Knowing how the community will feel about actions taken by the organization', 'Acting in ways that go against organizational norms. Thinking about issues from only the organization\'s perspective rather than the larger community', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(55, 15, 'Informing about new products', 'Informing a customer about a new product or service that would meet his / her needs', 'Waiting for the customer to ask you about a new product or service', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(56, 15, 'Discussing customer needs', 'Discussing with the customer his / her needs and satisfaction with current service', 'Assuming the customer will let know if there is a problem', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(57, 15, 'Following up on referrals', 'Following up with your colleagues or associates on customers you have referred to see if their needs have been met', 'Washing your hands of a customer problem by passing it on to someone else', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(58, 15, 'Understanding underlying needs', 'Finding ways to understand and meet real, underlying customer needs', 'Responding only to direct requests', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(59, 16, 'Remembering personal details', 'Remembering the names of your customer\'s children, talking about where they\'re going on holiday', 'Keeping things \"strictly business\" when taking to customers', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(60, 16, 'Building internal networks', 'Making a point of getting to know your colleagues in other stores / divisions', 'Making contact with other stores / divisions only when you need to solve a problem or get information', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(61, 16, 'Participating in community activities', 'Serving on a non-profit community board whose membership includes a potential contact', 'Turning down an invitation from a potential customer to become involved in a community group', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(62, 16, 'Maintaining long-term relationships', 'Working hard to build and maintain a long term relationship with a customer', 'Focusing on short-term interactions with a customer to build immediate revenue without considering the long term implications', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(63, 17, 'Taking on challenges', 'Willingly taking on a challenging new assignment', 'Hesitating to tackle a difficult project', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(64, 17, 'Defending ideas', 'Standing up for your ideas in the face of criticism or opposition from others, including more senior managers while being sensitive to other people\'s perspectives', 'Backing down, or remaining silent, when someone criticizes your position on an issue', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(65, 17, 'Acting on expertise', 'Taking action based on your expertise and understanding of the situation', 'Checking everything with your manager before proceeding', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(66, 18, 'Managing difficult customers', 'Ignoring rude behavior from a customer, focusing on calming them and then moving on to deal with the problem', 'Expressing strong emotions such as anger, frustration or fear in response to an intimidating customer', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(67, 18, 'Maintaining composure', 'Remaining polite and in control when serving rushed customers or dealing with malfunctioning systems', 'Giving into unreasonable demands; refusing to take personal responsibility to resolve the situation (blaming someone else)', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(68, 18, 'Calming others', 'Taking steps to calm someone (e.g. colleague or customer) who is upset', 'Refusing to get involved in emotionally charged situations or saying or doing things that make the situation worse', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(69, 18, 'Exploring alternatives', 'Maintaining an open perspective on a customer\'s concern or problem; exploring alternatives; displaying empathy', 'Refusing to look for alternative to resolve a customer\'s problem', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(70, 19, 'Communicating with team', 'Keeping team members informed about decisions and explaining the rationale behind them', 'Dictating orders to your team and giving out information on a \"need to know\" basis', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(71, 19, 'Setting direction', 'Setting direction and providing role clarity', 'Being nice to everyone (sometimes tough action is called for)', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(72, 19, 'Removing barriers', 'Clearing away bureaucratic barriers or other problems that are making it difficult for your staff to get the project done', 'Believing that if you want the job done right you need to do it yourself', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(73, 19, 'Enhancing morale', 'Making on-going efforts to enhance team and individual morale', 'Being the most technically competent person on the team', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(74, 20, 'Leveraging team skills', 'Drawing on the skills, ideas and viewpoints of other team members', 'Preferring to be left alone to get on with your own work', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(75, 20, 'Sharing information', 'Going out of your way to keep others informed and up-to-date on any potentially useful information', 'Attending team meetings without contributing', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(76, 20, 'Supporting team members', 'Supporting and encouraging team members', 'Engaging in win-lose competition with other members of the team', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02'),
(77, 20, 'Speaking positively about team', 'Speaking about team members in positive terms', 'Making negative comments about other team members', 1.00, 1, NULL, 1, '2025-08-28 10:19:02', '2025-08-28 10:19:02');

-- --------------------------------------------------------

--
-- Table structure for table `competency_categories`
--

CREATE TABLE `competency_categories` (
  `category_id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(20) DEFAULT NULL,
  `description` text,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `competency_categories`
--

INSERT INTO `competency_categories` (`category_id`, `category_name`, `category_code`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Achievement Orientation', 'ACHIEVE', 'Concern for meeting or exceeding standards of excellence', 1, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(2, 'Business/Strategic Orientation', 'BUSINESS', 'Understanding business implications and improving organizational performance', 2, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(3, 'Critical Thinking', 'CRITICAL', 'Breaking down complex situations and identifying key issues', 3, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(4, 'Concern for Order and Quality', 'ORDER', 'Drive to increase certainty in processes and environment', 4, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(5, 'Developing People', 'DEVELOP', 'Fostering long-term learning and development of others', 5, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(6, 'Directiveness', 'DIRECT', 'Making others comply with wishes appropriately', 6, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(7, 'Expertise', 'EXPERT', 'Distributing technical and procedural knowledge', 7, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(8, 'Flexibility', 'FLEX', 'Adapting approach as requirements change', 8, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(9, 'Impact and Influence', 'IMPACT', 'Influencing through persuasion and negotiation', 9, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(10, 'Information Seeking', 'INFO', 'Taking action to find out more', 10, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(11, 'Initiative', 'INIT', 'Bias for taking proactive action', 11, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(12, 'Innovation', 'INNOV', 'Generating new solutions and creative approaches', 12, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(13, 'Listening and Responding', 'LISTEN', 'Interacting effectively with others', 13, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(14, 'Organizational Awareness', 'ORG', 'Understanding power relationships in organizations', 14, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(15, 'Personalized Customer Service', 'CUSTOMER', 'Desire to help and serve others', 15, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(16, 'Relationship Building', 'RELATE', 'Building networks for work-related goals', 16, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(17, 'Self-Confidence', 'CONFIDENT', 'Belief in own capability', 17, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(18, 'Self-Control', 'CONTROL', 'Keeping emotions under control', 18, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(19, 'Team Leadership', 'LEAD', 'Leading teams effectively', 19, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58'),
(20, 'Teamwork', 'TEAM', 'Working cooperatively with others', 20, 1, '2025-08-27 06:27:58', '2025-08-27 06:27:58');

-- --------------------------------------------------------

--
-- Stand-in structure for view `competency_scores`
-- (See below for the actual view)
--
CREATE TABLE `competency_scores` (
`avg_score` decimal(7,5)
,`category_name` varchar(100)
,`competency_id` int
,`competency_name` varchar(200)
,`employee_count` bigint
,`period_id` int
,`period_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(20) DEFAULT NULL,
  `description` text,
  `manager_id` int DEFAULT NULL,
  `parent_department_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `description`, `manager_id`, `parent_department_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'HR', 'HR1', 'HR department', NULL, NULL, 1, '2025-08-28 08:12:15', '2025-08-28 08:12:15'),
(2, 'Human Resources', 'HR', 'Responsible for recruitment, training, and employee relations', NULL, NULL, 1, '2025-08-28 11:16:39', '2025-08-28 11:16:39'),
(3, 'Finance', 'FIN', 'Manages company finances and accounting', NULL, NULL, 1, '2025-08-28 11:16:39', '2025-08-28 11:16:39'),
(4, 'Marketing', 'MKT', 'Handles brand management and promotional activities', NULL, NULL, 1, '2025-08-28 11:16:39', '2025-08-28 11:16:39'),
(5, 'Operations', 'OPS', 'Manages day-to-day business operations', NULL, NULL, 1, '2025-08-28 11:16:39', '2025-08-28 11:16:39');

-- --------------------------------------------------------

--
-- Stand-in structure for view `department_scores`
-- (See below for the actual view)
--
CREATE TABLE `department_scores` (
`avg_score` decimal(7,5)
,`department_id` int
,`department_name` varchar(100)
,`employee_count` bigint
,`period_id` int
,`period_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int NOT NULL,
  `user_id` int NOT NULL,
  `employee_code` varchar(20) NOT NULL,
  `department_id` int DEFAULT NULL,
  `position_id` int DEFAULT NULL,
  `reporting_manager_id` int DEFAULT NULL,
  `hire_date` date NOT NULL,
  `employment_status` enum('active','on_leave','terminated','suspended') DEFAULT 'active',
  `employment_type` enum('full_time','part_time','contract','intern') DEFAULT 'full_time',
  `salary` decimal(10,2) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `user_id`, `employee_code`, `department_id`, `position_id`, `reporting_manager_id`, `hire_date`, `employment_status`, `employment_type`, `salary`, `phone_number`, `address`, `emergency_contact`, `emergency_phone`, `date_of_birth`, `gender`, `created_at`, `updated_at`) VALUES
(21, 7, 'EMP007', 2, 5, NULL, '2021-11-10', 'active', 'full_time', NULL, '+94112345680', '789 Negombo Road, Wattala', 'Kamal Rathnayake', '+94119876545', '1985-03-10', 'male', '2025-08-28 11:17:45', '2025-08-28 11:17:45'),
(22, 10, 'EMP010', 3, 7, NULL, '2021-09-25', 'active', 'full_time', NULL, '+94112345683', '987 Anuradhapura Road, Kelaniya', 'Priyantha Gunawardena', '+94119876548', '1988-12-05', 'male', '2025-08-28 11:17:45', '2025-08-28 11:17:45'),
(23, 11, 'EMP011', 4, 9, NULL, '2020-06-15', 'active', 'full_time', NULL, '+94112345684', '159 Badulla Road, Kiribathgoda', 'Saman Jayawardena', '+94119876549', '1982-04-25', 'female', '2025-08-28 11:17:45', '2025-08-28 11:17:45');

-- --------------------------------------------------------

--
-- Table structure for table `employee_goals`
--

CREATE TABLE `employee_goals` (
  `goal_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `evaluation_id` int DEFAULT NULL,
  `goal_title` varchar(200) NOT NULL,
  `goal_description` text,
  `target_date` date DEFAULT NULL,
  `status` enum('not_started','in_progress','completed','cancelled') DEFAULT 'not_started',
  `completion_percentage` int DEFAULT '0',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `evaluation_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `evaluator_id` int DEFAULT NULL,
  `period_id` int NOT NULL,
  `evaluation_type` enum('self','manager','peer','360') DEFAULT 'manager',
  `status` enum('not_started','draft','in_progress','submitted','reviewed','approved','rejected') DEFAULT 'not_started',
  `overall_score` decimal(3,1) DEFAULT NULL,
  `overall_rating` enum('exceptional','exceeds_expectations','meets_expectations','needs_improvement','unsatisfactory') DEFAULT NULL,
  `general_comments` text,
  `strengths` text,
  `areas_for_improvement` text,
  `goals_next_period` text,
  `submitted_date` datetime DEFAULT NULL,
  `reviewed_date` datetime DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `evaluations`
--
DELIMITER $$
CREATE TRIGGER `log_evaluation_status_change` AFTER UPDATE ON `evaluations` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO evaluation_history (evaluation_id, changed_by, change_type, old_value, new_value)
        VALUES (NEW.evaluation_id, NEW.evaluator_id, 'status_change', OLD.status, NEW.status);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_history`
--

CREATE TABLE `evaluation_history` (
  `history_id` int NOT NULL,
  `evaluation_id` int NOT NULL,
  `changed_by` int DEFAULT NULL,
  `change_type` varchar(50) DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `change_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_periods`
--

CREATE TABLE `evaluation_periods` (
  `period_id` int NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `evaluation_deadline` date DEFAULT NULL,
  `period_type` enum('monthly','quarterly','bi_annual','annual') NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `evaluation_periods`
--

INSERT INTO `evaluation_periods` (`period_id`, `period_name`, `start_date`, `end_date`, `evaluation_deadline`, `period_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'DT-QC-Evaluation', '2025-08-01', '2025-12-31', '2026-01-31', 'monthly', 1, '2025-08-28 03:50:49', '2025-08-28 03:50:49');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_scores`
--

CREATE TABLE `evaluation_scores` (
  `score_id` int NOT NULL,
  `evaluation_id` int NOT NULL,
  `competency_id` int NOT NULL,
  `score` decimal(3,1) NOT NULL,
  `rating` enum('exceptional','exceeds_expectations','meets_expectations','needs_improvement','unsatisfactory') DEFAULT NULL,
  `comments` text,
  `evidence` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `evaluation_scores`
--
DELIMITER $$
CREATE TRIGGER `update_overall_score_on_insert` AFTER INSERT ON `evaluation_scores` FOR EACH ROW BEGIN
    CALL CalculateOverallScore(NEW.evaluation_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_overall_score_on_update` AFTER UPDATE ON `evaluation_scores` FOR EACH ROW BEGIN
    CALL CalculateOverallScore(NEW.evaluation_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `evaluation_summary`
-- (See below for the actual view)
--
CREATE TABLE `evaluation_summary` (
`department_name` varchar(100)
,`employee_code` varchar(20)
,`employee_name` varchar(101)
,`evaluation_id` int
,`evaluation_type` enum('self','manager','peer','360')
,`evaluator_name` varchar(101)
,`overall_rating` enum('exceptional','exceeds_expectations','meets_expectations','needs_improvement','unsatisfactory')
,`overall_score` decimal(3,1)
,`period_name` varchar(50)
,`position_title` varchar(100)
,`status` enum('not_started','draft','in_progress','submitted','reviewed','approved','rejected')
,`submitted_date` datetime
);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `given_by` int NOT NULL,
  `feedback_type` enum('positive','constructive','developmental') NOT NULL,
  `feedback_text` text NOT NULL,
  `is_private` tinyint(1) DEFAULT '0',
  `acknowledged` tinyint(1) DEFAULT '0',
  `acknowledged_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int NOT NULL,
  `user_id` int NOT NULL,
  `notification_type` enum('evaluation_due','evaluation_submitted','evaluation_approved','feedback_received','goal_due') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_code` varchar(50) NOT NULL,
  `description` text,
  `module` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_name`, `permission_code`, `description`, `module`, `created_at`) VALUES
(1, 'View All Evaluations', 'view_all_evaluations', NULL, 'evaluation', '2025-08-27 06:41:11'),
(2, 'Create Evaluation', 'create_evaluation', NULL, 'evaluation', '2025-08-27 06:41:11'),
(3, 'Edit Evaluation', 'edit_evaluation', NULL, 'evaluation', '2025-08-27 06:41:11'),
(4, 'Approve Evaluation', 'approve_evaluation', NULL, 'evaluation', '2025-08-27 06:41:11'),
(5, 'View Reports', 'view_reports', NULL, 'reports', '2025-08-27 06:41:11'),
(6, 'Manage Employees', 'manage_employees', NULL, 'employees', '2025-08-27 06:41:11'),
(7, 'Manage Settings', 'manage_settings', NULL, 'settings', '2025-08-27 06:41:11'),
(8, 'View Own Evaluation', 'view_own_evaluation', NULL, 'evaluation', '2025-08-27 06:41:11'),
(9, 'Submit Self Evaluation', 'submit_self_evaluation', NULL, 'evaluation', '2025-08-27 06:41:11');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `position_id` int NOT NULL,
  `position_title` varchar(100) NOT NULL,
  `position_level` int DEFAULT NULL,
  `min_salary` decimal(10,2) DEFAULT NULL,
  `max_salary` decimal(10,2) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`position_id`, `position_title`, `position_level`, `min_salary`, `max_salary`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Software Developer', 5, 80000.00, 120000.00, 'Develops and maintains software applications', '2025-08-28 11:17:07', '2025-08-28 11:17:07'),
(2, 'Senior Software Developer', 6, 100000.00, 150000.00, 'Leads software development projects', '2025-08-28 11:17:07', '2025-08-28 11:17:07'),
(3, 'IT Manager', 7, 120000.00, 180000.00, 'Manages IT department and resources', '2025-08-28 11:17:07', '2025-08-28 11:17:07'),
(4, 'HR Specialist', 4, 60000.00, 90000.00, 'Handles employee relations and recruitment', '2025-08-28 11:17:07', '2025-08-28 11:17:07'),
(5, 'HR Manager', 6, 90000.00, 130000.00, 'Leads HR department and strategies', '2025-08-28 11:17:07', '2025-08-28 11:17:07'),
(6, 'Financial Analyst', 5, 70000.00, 110000.00, 'Analyzes financial data and prepares reports', '2025-08-28 11:17:07', '2025-08-28 11:17:07'),
(7, 'Finance Manager', 7, 110000.00, 160000.00, 'Manages company finances and budgeting', '2025-08-28 11:17:07', '2025-08-28 11:17:07'),
(8, 'Marketing Specialist', 4, 65000.00, 95000.00, 'Develops and implements marketing campaigns', '2025-08-28 11:17:07', '2025-08-28 11:17:07'),
(9, 'Marketing Manager', 6, 95000.00, 140000.00, 'Leads marketing department and strategies', '2025-08-28 11:17:07', '2025-08-28 11:17:07'),
(10, 'Operations Coordinator', 4, 60000.00, 85000.00, 'Coordinates daily operational activities', '2025-08-28 11:17:07', '2025-08-28 11:17:07');

-- --------------------------------------------------------

--
-- Table structure for table `position_competencies`
--

CREATE TABLE `position_competencies` (
  `position_competency_id` int NOT NULL,
  `position_id` int NOT NULL,
  `competency_id` int NOT NULL,
  `is_required` tinyint(1) DEFAULT '1',
  `weight_override` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_permission_id` int NOT NULL,
  `role` varchar(50) NOT NULL,
  `permission_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_permission_id`, `role`, `permission_id`, `created_at`) VALUES
(1, 'employee', 9, '2025-08-27 06:41:47'),
(2, 'employee', 8, '2025-08-27 06:41:47'),
(4, 'manager', 2, '2025-08-27 06:41:47'),
(5, 'manager', 3, '2025-08-27 06:41:47'),
(6, 'manager', 9, '2025-08-27 06:41:47'),
(7, 'manager', 8, '2025-08-27 06:41:47'),
(8, 'manager', 5, '2025-08-27 06:41:47'),
(11, 'hr_admin', 4, '2025-08-27 06:41:47'),
(12, 'hr_admin', 2, '2025-08-27 06:41:47'),
(13, 'hr_admin', 3, '2025-08-27 06:41:47'),
(14, 'hr_admin', 6, '2025-08-27 06:41:47'),
(15, 'hr_admin', 7, '2025-08-27 06:41:47'),
(16, 'hr_admin', 9, '2025-08-27 06:41:47'),
(17, 'hr_admin', 1, '2025-08-27 06:41:47'),
(18, 'hr_admin', 8, '2025-08-27 06:41:47'),
(19, 'hr_admin', 5, '2025-08-27 06:41:47'),
(26, 'system_admin', 4, '2025-08-27 06:41:47'),
(27, 'system_admin', 2, '2025-08-27 06:41:47'),
(28, 'system_admin', 3, '2025-08-27 06:41:47'),
(29, 'system_admin', 6, '2025-08-27 06:41:47'),
(30, 'system_admin', 7, '2025-08-27 06:41:47'),
(31, 'system_admin', 9, '2025-08-27 06:41:47'),
(32, 'system_admin', 1, '2025-08-27 06:41:47'),
(33, 'system_admin', 8, '2025-08-27 06:41:47'),
(34, 'system_admin', 5, '2025-08-27 06:41:47');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','integer','decimal','boolean','json') DEFAULT 'string',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'evaluation_frequency', 'quarterly', 'string', 'Default evaluation frequency', '2025-08-27 06:40:37', '2025-08-27 06:40:37'),
(2, 'min_score_threshold', '5', 'decimal', 'Minimum acceptable score', '2025-08-27 06:40:37', '2025-08-27 06:40:37'),
(3, 'auto_reminder_enabled', 'true', 'boolean', 'Send automatic evaluation reminders', '2025-08-27 06:40:37', '2025-08-27 06:40:37'),
(4, 'self_evaluation_enabled', 'true', 'boolean', 'Allow self-evaluations', '2025-08-27 06:40:37', '2025-08-27 06:40:37'),
(5, 'reminder_days_before', '7', 'integer', 'Days before deadline to send reminder', '2025-08-27 06:40:37', '2025-08-27 06:40:37'),
(6, 'max_login_attempts', '5', 'integer', 'Maximum login attempts before lockout', '2025-08-27 06:40:37', '2025-08-27 06:40:37'),
(7, 'session_timeout', '30', 'integer', 'Session timeout in minutes', '2025-08-27 06:40:37', '2025-08-27 06:40:37'),
(8, 'password_min_length', '8', 'integer', 'Minimum password length', '2025-08-27 06:40:37', '2025-08-27 06:40:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('employee','manager','hr_admin','system_admin') NOT NULL DEFAULT 'employee',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`, `last_login`, `password_reset_token`, `password_reset_expires`, `created_at`, `updated_at`) VALUES
(1, 'john.manager', 'john.manager@company.com', '$2y$10$YourHashedPasswordHere', 'John', 'Manager', 'manager', 1, NULL, NULL, NULL, '2025-08-27 06:46:34', '2025-08-27 06:46:34'),
(2, 'sarah.johnson', 'sarah.johnson@company.com', '$2a$12$SQF.UXkgc5Jz5IpmzEIgwu6mIfIrtuMA00tK7UfMAzYQCW2RzrilK', 'Sarah', 'Johnson', 'employee', 1, NULL, NULL, NULL, '2025-08-27 06:46:34', '2025-08-29 06:13:25'),
(3, 'mike.davis', 'mike.davis@company.com', '$2y$10$YourHashedPasswordHere', 'Mike', 'Davis', 'employee', 1, NULL, NULL, NULL, '2025-08-27 06:46:34', '2025-08-27 06:46:34'),
(4, 'hr.admin', 'hr.admin@company.com', '$2a$12$eM9.4vuk64JhRILSTqoCauXjDM1kLQq3S00IhlbSzwI4dP4fLPzWu', 'Jane', 'Smith', 'hr_admin', 1, NULL, NULL, NULL, '2025-08-27 06:46:34', '2025-08-27 09:44:18'),
(5, 'amal.perera', 'amal.perera@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amal', 'Perera', 'employee', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09'),
(6, 'nimal.fernando', 'nimal.fernando@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nimal', 'Fernando', 'employee', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09'),
(7, 'sunil.rathnayake', 'sunil.rathnayake@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sunil', 'Rathnayake', 'manager', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09'),
(8, 'kamala.silva', 'kamala.silva@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kamala', 'Silva', 'employee', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09'),
(9, 'priyanka.bandara', 'priyanka.bandara@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priyanka', 'Bandara', 'employee', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09'),
(10, 'sampath.gunawardena', 'sampath.gunawardena@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sampath', 'Gunawardena', 'employee', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09'),
(11, 'dilani.jayawardena', 'dilani.jayawardena@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dilani', 'Jayawardena', 'hr_admin', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09'),
(12, 'roshan.weerasinghe', 'roshan.weerasinghe@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Roshan', 'Weerasinghe', 'employee', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09'),
(13, 'chathura.dissanayake', 'chathura.dissanayake@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chathura', 'Dissanayake', 'employee', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09'),
(14, 'anoma.wickramasinghe', 'anoma.wickramasinghe@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anoma', 'Wickramasinghe', 'employee', 1, NULL, NULL, NULL, '2025-08-28 11:12:09', '2025-08-28 11:12:09');

-- --------------------------------------------------------

--
-- Structure for view `competency_scores`
--
DROP TABLE IF EXISTS `competency_scores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`speedsri`@`%` SQL SECURITY DEFINER VIEW `competency_scores`  AS SELECT `c`.`competency_id` AS `competency_id`, `c`.`competency_name` AS `competency_name`, `cc`.`category_name` AS `category_name`, `ep`.`period_id` AS `period_id`, `ep`.`period_name` AS `period_name`, avg(`es`.`score`) AS `avg_score`, count(distinct `e`.`employee_id`) AS `employee_count` FROM ((((`competencies` `c` join `competency_categories` `cc` on((`c`.`category_id` = `cc`.`category_id`))) join `evaluation_scores` `es` on((`c`.`competency_id` = `es`.`competency_id`))) join `evaluations` `e` on((`es`.`evaluation_id` = `e`.`evaluation_id`))) join `evaluation_periods` `ep` on((`e`.`period_id` = `ep`.`period_id`))) WHERE (`e`.`status` = 'approved') GROUP BY `c`.`competency_id`, `ep`.`period_id` ;

-- --------------------------------------------------------

--
-- Structure for view `department_scores`
--
DROP TABLE IF EXISTS `department_scores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`speedsri`@`%` SQL SECURITY DEFINER VIEW `department_scores`  AS SELECT `d`.`department_id` AS `department_id`, `d`.`department_name` AS `department_name`, `ep`.`period_id` AS `period_id`, `ep`.`period_name` AS `period_name`, avg(`e`.`overall_score`) AS `avg_score`, count(distinct `e`.`employee_id`) AS `employee_count` FROM (((`departments` `d` join `employees` `emp` on((`d`.`department_id` = `emp`.`department_id`))) join `evaluations` `e` on((`emp`.`employee_id` = `e`.`employee_id`))) join `evaluation_periods` `ep` on((`e`.`period_id` = `ep`.`period_id`))) WHERE (`e`.`status` = 'approved') GROUP BY `d`.`department_id`, `ep`.`period_id` ;

-- --------------------------------------------------------

--
-- Structure for view `evaluation_summary`
--
DROP TABLE IF EXISTS `evaluation_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`speedsri`@`%` SQL SECURITY DEFINER VIEW `evaluation_summary`  AS SELECT `e`.`evaluation_id` AS `evaluation_id`, `emp`.`employee_code` AS `employee_code`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `employee_name`, `d`.`department_name` AS `department_name`, `p`.`position_title` AS `position_title`, `ep`.`period_name` AS `period_name`, `e`.`evaluation_type` AS `evaluation_type`, `e`.`overall_score` AS `overall_score`, `e`.`overall_rating` AS `overall_rating`, `e`.`status` AS `status`, `e`.`submitted_date` AS `submitted_date`, concat(`ev_u`.`first_name`,' ',`ev_u`.`last_name`) AS `evaluator_name` FROM (((((((`evaluations` `e` join `employees` `emp` on((`e`.`employee_id` = `emp`.`employee_id`))) join `users` `u` on((`emp`.`user_id` = `u`.`user_id`))) join `employees` `ev_emp` on((`e`.`evaluator_id` = `ev_emp`.`employee_id`))) join `users` `ev_u` on((`ev_emp`.`user_id` = `ev_u`.`user_id`))) left join `departments` `d` on((`emp`.`department_id` = `d`.`department_id`))) left join `positions` `p` on((`emp`.`position_id` = `p`.`position_id`))) join `evaluation_periods` `ep` on((`e`.`period_id` = `ep`.`period_id`))) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_user_audit` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `competencies`
--
ALTER TABLE `competencies`
  ADD PRIMARY KEY (`competency_id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `competency_categories`
--
ALTER TABLE `competency_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_code` (`category_code`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code` (`department_code`),
  ADD KEY `parent_department_id` (`parent_department_id`),
  ADD KEY `idx_dept_name` (`department_name`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `idx_emp_code` (`employee_code`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_manager` (`reporting_manager_id`),
  ADD KEY `idx_status` (`employment_status`),
  ADD KEY `idx_employee_active` (`employment_status`,`department_id`);

--
-- Indexes for table `employee_goals`
--
ALTER TABLE `employee_goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD KEY `evaluation_id` (`evaluation_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_employee_goals` (`employee_id`),
  ADD KEY `idx_status_goals` (`status`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD UNIQUE KEY `unique_evaluation` (`employee_id`,`evaluator_id`,`period_id`,`evaluation_type`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_evaluator` (`evaluator_id`),
  ADD KEY `idx_period` (`period_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_eval_employee_period` (`employee_id`,`period_id`),
  ADD KEY `idx_eval_status_period` (`status`,`period_id`);

--
-- Indexes for table `evaluation_history`
--
ALTER TABLE `evaluation_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_evaluation_hist` (`evaluation_id`);

--
-- Indexes for table `evaluation_periods`
--
ALTER TABLE `evaluation_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `evaluation_scores`
--
ALTER TABLE `evaluation_scores`
  ADD PRIMARY KEY (`score_id`),
  ADD UNIQUE KEY `unique_eval_competency` (`evaluation_id`,`competency_id`),
  ADD KEY `competency_id` (`competency_id`),
  ADD KEY `idx_evaluation` (`evaluation_id`),
  ADD KEY `idx_score_evaluation` (`evaluation_id`,`score`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `idx_employee_feedback` (`employee_id`),
  ADD KEY `idx_giver` (`given_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_notif` (`user_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`),
  ADD UNIQUE KEY `permission_code` (`permission_code`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`position_id`);

--
-- Indexes for table `position_competencies`
--
ALTER TABLE `position_competencies`
  ADD PRIMARY KEY (`position_competency_id`),
  ADD UNIQUE KEY `unique_position_competency` (`position_id`,`competency_id`),
  ADD KEY `competency_id` (`competency_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_permission_id`),
  ADD UNIQUE KEY `unique_role_permission` (`role`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_user_active` (`is_active`,`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `audit_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competencies`
--
ALTER TABLE `competencies`
  MODIFY `competency_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `competency_categories`
--
ALTER TABLE `competency_categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `employee_goals`
--
ALTER TABLE `employee_goals`
  MODIFY `goal_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `evaluation_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_history`
--
ALTER TABLE `evaluation_history`
  MODIFY `history_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_periods`
--
ALTER TABLE `evaluation_periods`
  MODIFY `period_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `evaluation_scores`
--
ALTER TABLE `evaluation_scores`
  MODIFY `score_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `position_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `position_competencies`
--
ALTER TABLE `position_competencies`
  MODIFY `position_competency_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `role_permission_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `competencies`
--
ALTER TABLE `competencies`
  ADD CONSTRAINT `competencies_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `competency_categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`parent_department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `departments_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_3` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_4` FOREIGN KEY (`reporting_manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_goals`
--
ALTER TABLE `employee_goals`
  ADD CONSTRAINT `employee_goals_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_goals_ibfk_2` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`evaluation_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_goals_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `evaluations_ibfk_3` FOREIGN KEY (`period_id`) REFERENCES `evaluation_periods` (`period_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_4` FOREIGN KEY (`reviewed_by`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `evaluations_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `evaluation_history`
--
ALTER TABLE `evaluation_history`
  ADD CONSTRAINT `evaluation_history_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`evaluation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluation_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `evaluation_scores`
--
ALTER TABLE `evaluation_scores`
  ADD CONSTRAINT `evaluation_scores_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`evaluation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluation_scores_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`competency_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`given_by`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `position_competencies`
--
ALTER TABLE `position_competencies`
  ADD CONSTRAINT `position_competencies_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `position_competencies_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`competency_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
