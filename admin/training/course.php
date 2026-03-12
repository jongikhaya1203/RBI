<?php
/**
 * Course Viewer - RBI Engineering Suite Training Module
 * Individual course with lesson content, progress tracking, and quizzes
 */
$pageTitle = 'Course Viewer';
$pageSection = 'Training';
$currentModule = 'admin';

require_once dirname(dirname(__DIR__)) . '/config/app.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    flash('Please log in to continue.', 'warning');
    redirect(BASE_URL . '/login.php');
}

$courseId = (int)($_GET['id'] ?? 0);

// Full course data with lesson content and quizzes
$allCourses = [
    1 => [
        'title' => 'Introduction to RBI',
        'difficulty' => 'Beginner',
        'duration' => '2 hours',
        'icon' => 'fa-book-open',
        'color' => 'primary',
        'category' => 'RBI Fundamentals',
        'lessons' => [
            1 => [
                'title' => 'What is Risk-Based Inspection?',
                'content' => '<h4>What is Risk-Based Inspection?</h4>
<p>Risk-Based Inspection (RBI) is a methodology for developing an inspection plan based on the knowledge of the risk of failure of equipment in a processing facility. RBI prioritizes and manages the effort of an inspection program by providing a tool to identify the areas of highest risk and direct inspection resources toward those areas.</p>

<h5>Key Concepts</h5>
<ul>
<li><strong>Risk</strong> = Probability of Failure (PoF) x Consequence of Failure (CoF)</li>
<li><strong>Probability of Failure:</strong> The likelihood that a given piece of equipment will develop a leak or rupture</li>
<li><strong>Consequence of Failure:</strong> The impact on safety, health, environment, and business if failure occurs</li>
</ul>

<h5>The RBI Framework</h5>
<p>The RBI framework provides a structured approach to:</p>
<ol>
<li>Identify equipment and degradation mechanisms</li>
<li>Assess the probability and consequences of failure</li>
<li>Calculate risk values</li>
<li>Develop risk-based inspection plans</li>
<li>Continuously update and improve the inspection program</li>
</ol>

<div class="alert alert-info mt-3">
<i class="fas fa-lightbulb me-2"></i><strong>Key Takeaway:</strong> RBI is not about inspecting less -- it is about inspecting smarter by focusing resources on the highest risk equipment.
</div>'
            ],
            2 => [
                'title' => 'History and Evolution of RBI',
                'content' => '<h4>History and Evolution of RBI</h4>
<p>The concept of Risk-Based Inspection evolved from the recognition that traditional time-based inspection was not the most effective approach for managing equipment integrity.</p>

<h5>Timeline of Key Developments</h5>
<table class="table table-bordered">
<tr><td><strong>1980s</strong></td><td>Early risk assessment concepts applied to nuclear industry</td></tr>
<tr><td><strong>1990s</strong></td><td>Petroleum and chemical industries begin adopting risk principles</td></tr>
<tr><td><strong>1996</strong></td><td>API publishes first RBI guidance document (API Publ 581)</td></tr>
<tr><td><strong>2002</strong></td><td>API 580 first edition - Risk-Based Inspection standard</td></tr>
<tr><td><strong>2008</strong></td><td>API 581 second edition with quantitative methodology</td></tr>
<tr><td><strong>2016</strong></td><td>API 580 third edition - enhanced risk management framework</td></tr>
<tr><td><strong>2016</strong></td><td>API 581 third edition - updated damage factors and consequence models</td></tr>
<tr><td><strong>2020s</strong></td><td>Integration with digital technologies, ML/AI, IoT sensors</td></tr>
</table>

<h5>Industry Adoption</h5>
<p>Today, RBI is widely adopted across:</p>
<ul>
<li>Oil refining</li>
<li>Petrochemical processing</li>
<li>Chemical manufacturing</li>
<li>Upstream oil and gas</li>
<li>Power generation</li>
<li>Pharmaceutical manufacturing</li>
</ul>'
            ],
            3 => [
                'title' => 'Benefits of RBI Programs',
                'content' => '<h4>Benefits of RBI Programs</h4>

<h5>Safety Benefits</h5>
<ul>
<li>Reduced risk of catastrophic failures through focused inspection</li>
<li>Better identification of high-risk equipment before failures occur</li>
<li>Improved understanding of degradation mechanisms</li>
<li>Enhanced process safety management integration</li>
</ul>

<h5>Economic Benefits</h5>
<div class="row mb-3">
<div class="col-md-6">
<div class="card bg-light p-3">
<h6>Cost Reductions</h6>
<ul>
<li>25-50% reduction in inspection costs</li>
<li>Fewer unnecessary inspections on low-risk equipment</li>
<li>Extended turnaround intervals where justified</li>
<li>Reduced unplanned shutdowns</li>
</ul>
</div>
</div>
<div class="col-md-6">
<div class="card bg-light p-3">
<h6>Efficiency Gains</h6>
<ul>
<li>Optimized inspection resource allocation</li>
<li>Better planning for turnarounds</li>
<li>Focused NDE technique selection</li>
<li>Data-driven decision making</li>
</ul>
</div>
</div>
</div>

<h5>Regulatory Benefits</h5>
<ul>
<li>Demonstrates compliance with OSHA PSM requirements</li>
<li>Supports Mechanical Integrity (MI) programs</li>
<li>Provides documented basis for inspection decisions</li>
<li>Meets RAGAGEP requirements</li>
</ul>

<div class="alert alert-success mt-3">
<i class="fas fa-chart-line me-2"></i><strong>Industry Data:</strong> Companies implementing RBI typically see a 25-50% reduction in inspection costs while simultaneously improving safety performance.
</div>'
            ],
            4 => [
                'title' => 'RBI vs Time-Based Inspection',
                'content' => '<h4>RBI vs Time-Based Inspection</h4>

<h5>Time-Based Inspection (TBI)</h5>
<p>Traditional approach where all equipment is inspected at fixed intervals regardless of risk level. Typically based on code requirements, company standards, or industry practices.</p>

<h5>Comparison Table</h5>
<table class="table table-bordered table-striped">
<thead class="table-dark"><tr><th>Aspect</th><th>Time-Based</th><th>Risk-Based</th></tr></thead>
<tbody>
<tr><td>Inspection Frequency</td><td>Fixed intervals (e.g., 5 years)</td><td>Based on calculated risk</td></tr>
<tr><td>Resource Allocation</td><td>Equal attention to all equipment</td><td>Focused on highest risk items</td></tr>
<tr><td>NDE Method Selection</td><td>Standard for all similar equipment</td><td>Targeted to specific degradation</td></tr>
<tr><td>Cost Efficiency</td><td>Often over-inspects low risk items</td><td>Optimized spend per risk reduction</td></tr>
<tr><td>Safety Effectiveness</td><td>May miss high-risk items between intervals</td><td>Higher confidence in risk management</td></tr>
<tr><td>Documentation</td><td>Inspection records only</td><td>Full risk assessment documentation</td></tr>
<tr><td>Flexibility</td><td>Rigid schedules</td><td>Adaptive to changing conditions</td></tr>
</tbody>
</table>

<div class="alert alert-warning mt-3">
<i class="fas fa-exclamation-triangle me-2"></i><strong>Important:</strong> RBI does not replace code or regulatory minimum requirements. It works within these frameworks to optimize the inspection effort.
</div>'
            ],
            5 => [
                'title' => 'Key Terminology',
                'content' => '<h4>Key RBI Terminology</h4>

<div class="row g-3">
<div class="col-md-6">
<div class="card p-3 h-100">
<h6 class="text-primary">PoF - Probability of Failure</h6>
<p class="small mb-0">The likelihood that a piece of equipment will fail due to an active damage mechanism within a specified time period.</p>
</div>
</div>
<div class="col-md-6">
<div class="card p-3 h-100">
<h6 class="text-primary">CoF - Consequence of Failure</h6>
<p class="small mb-0">The expected outcome/impact of a failure event, including safety, environmental, and financial consequences.</p>
</div>
</div>
<div class="col-md-6">
<div class="card p-3 h-100">
<h6 class="text-primary">Damage Factor (DF)</h6>
<p class="small mb-0">A numerical value representing the amount of damage expected from a specific damage mechanism, used to calculate probability of failure.</p>
</div>
</div>
<div class="col-md-6">
<div class="card p-3 h-100">
<h6 class="text-primary">CML - Condition Monitoring Location</h6>
<p class="small mb-0">A designated location on equipment where thickness measurements or other inspections are performed regularly.</p>
</div>
</div>
<div class="col-md-6">
<div class="card p-3 h-100">
<h6 class="text-primary">Corrosion Rate</h6>
<p class="small mb-0">The rate at which material is lost due to corrosion, typically measured in mils per year (mpy) or mm/year.</p>
</div>
</div>
<div class="col-md-6">
<div class="card p-3 h-100">
<h6 class="text-primary">Remaining Life</h6>
<p class="small mb-0">The calculated time until equipment reaches its minimum required thickness or is no longer fit for service.</p>
</div>
</div>
<div class="col-md-6">
<div class="card p-3 h-100">
<h6 class="text-primary">MAWP - Maximum Allowable Working Pressure</h6>
<p class="small mb-0">The maximum pressure at which equipment is permitted to operate at a designated temperature.</p>
</div>
</div>
<div class="col-md-6">
<div class="card p-3 h-100">
<h6 class="text-primary">Risk Matrix</h6>
<p class="small mb-0">A visual tool that plots equipment on a grid of probability vs. consequence to categorize risk levels.</p>
</div>
</div>
</div>'
            ],
        ],
        'quiz' => [
            ['q' => 'What is the fundamental equation for risk in RBI?', 'options' => ['Risk = PoF + CoF', 'Risk = PoF x CoF', 'Risk = PoF / CoF', 'Risk = PoF - CoF'], 'answer' => 1],
            ['q' => 'When was API 580 first published?', 'options' => ['1996', '2000', '2002', '2008'], 'answer' => 2],
            ['q' => 'What does PoF stand for?', 'options' => ['Point of Failure', 'Probability of Failure', 'Process of Failure', 'Percentage of Failure'], 'answer' => 1],
            ['q' => 'What is a typical cost reduction when implementing RBI?', 'options' => ['5-10%', '10-15%', '25-50%', '75-90%'], 'answer' => 2],
            ['q' => 'What does CML stand for?', 'options' => ['Corrosion Measurement Location', 'Condition Monitoring Location', 'Chemical Material Loss', 'Controlled Mechanical Loading'], 'answer' => 1],
            ['q' => 'Which is NOT a benefit of RBI?', 'options' => ['Reduced inspection costs', 'Better safety performance', 'Elimination of all inspections', 'Optimized resource allocation'], 'answer' => 2],
            ['q' => 'In time-based inspection, intervals are determined by:', 'options' => ['Risk calculations', 'Fixed schedules', 'Random selection', 'Equipment color'], 'answer' => 1],
            ['q' => 'RBI was first developed for which industry?', 'options' => ['Automotive', 'Nuclear', 'Aerospace', 'Food processing'], 'answer' => 1],
            ['q' => 'What does MAWP stand for?', 'options' => ['Maximum Allowable Working Pressure', 'Minimum Acceptable Working Parameters', 'Maximum Applied Wall Pressure', 'Measured Average Wall Position'], 'answer' => 0],
            ['q' => 'A Risk Matrix plots which two factors?', 'options' => ['Cost vs Time', 'Probability vs Consequence', 'Temperature vs Pressure', 'Thickness vs Age'], 'answer' => 1],
        ]
    ],
    2 => [
        'title' => 'API 580 - Risk-Based Inspection',
        'difficulty' => 'Intermediate',
        'duration' => '4 hours',
        'icon' => 'fa-file-contract',
        'color' => 'warning',
        'category' => 'API 580/581',
        'lessons' => [
            1 => ['title' => 'Scope and Applicability', 'content' => '<h4>API 580: Scope and Applicability</h4>
<p>API 580 provides general guidance on developing a Risk-Based Inspection program for fixed equipment and piping in the hydrocarbon and chemical process industries.</p>
<h5>Scope</h5>
<ul><li>Applicable to fixed pressure equipment including vessels, piping, tanks, boilers, heaters, and heat exchangers</li>
<li>Covers static equipment - does not include rotating equipment</li>
<li>Applicable to refineries, petrochemical plants, chemical plants, E&P facilities, and pipelines</li></ul>
<h5>Applicability Requirements</h5>
<p>An RBI program under API 580 requires:</p>
<ol><li>Management commitment and support</li><li>Qualified RBI team members</li><li>Adequate data and documentation</li><li>Defined scope and objectives</li><li>Ongoing program maintenance and updates</li></ol>
<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>API 580 is a recommended practice, not a code. It provides the framework, while API 581 provides the specific quantitative methodology.</div>'],
            2 => ['title' => 'RBI Program Development', 'content' => '<h4>RBI Program Development</h4>
<p>Developing an effective RBI program requires a systematic approach involving multiple stakeholders and disciplines.</p>
<h5>Program Development Steps</h5>
<ol><li><strong>Define Objectives:</strong> Establish clear goals for safety, reliability, and cost optimization</li>
<li><strong>Assemble the Team:</strong> Include corrosion engineers, inspectors, process engineers, operations personnel</li>
<li><strong>Define Scope:</strong> Determine which units, systems, and equipment to include</li>
<li><strong>Gather Data:</strong> Collect design data, operating history, inspection records, and process information</li>
<li><strong>Assess Risk:</strong> Evaluate probability and consequence of failure for each component</li>
<li><strong>Develop Inspection Plans:</strong> Create risk-based inspection plans with appropriate methods and intervals</li>
<li><strong>Implement:</strong> Execute the inspection plans and document results</li>
<li><strong>Review and Update:</strong> Continuously improve the program based on new data</li></ol>
<h5>Team Qualifications</h5>
<p>The RBI team should include expertise in:</p>
<ul><li>Materials science and corrosion engineering</li><li>Process engineering and operations</li><li>Inspection and NDE techniques</li><li>Risk assessment methodology</li><li>Mechanical integrity programs</li></ul>'],
            3 => ['title' => 'Data Collection and Validation', 'content' => '<h4>Data Collection and Validation</h4>
<p>The quality of an RBI assessment is directly dependent on the quality and completeness of the input data.</p>
<h5>Essential Data Categories</h5>
<table class="table table-bordered"><thead class="table-dark"><tr><th>Category</th><th>Data Elements</th></tr></thead>
<tbody>
<tr><td><strong>Design Data</strong></td><td>Materials of construction, design pressure/temperature, wall thickness, corrosion allowance, MAWP</td></tr>
<tr><td><strong>Operating Data</strong></td><td>Operating pressure/temperature, process fluid composition, flow rates, upset conditions</td></tr>
<tr><td><strong>Inspection Data</strong></td><td>Thickness measurements, inspection dates, NDE results, repair history</td></tr>
<tr><td><strong>Process Data</strong></td><td>Fluid properties, chemical composition, H2S content, acid concentration, water content</td></tr>
<tr><td><strong>Maintenance Data</strong></td><td>Repair records, replacement history, modification records</td></tr>
</tbody></table>
<h5>Data Validation</h5>
<p>All data should be validated for:</p>
<ul><li>Accuracy - verified against original sources</li><li>Completeness - all required fields populated</li><li>Consistency - no conflicting information</li><li>Currency - data is up to date</li></ul>'],
            4 => ['title' => 'Consequence Analysis', 'content' => '<h4>Consequence Analysis</h4>
<p>Consequence analysis evaluates the potential outcomes of equipment failure, considering safety, environmental, and financial impacts.</p>
<h5>Consequence Categories</h5>
<ul><li><strong>Safety:</strong> Potential for injury or fatality to personnel</li>
<li><strong>Environmental:</strong> Release of hazardous materials to the environment</li>
<li><strong>Financial:</strong> Equipment damage, production loss, cleanup costs, liability</li></ul>
<h5>Factors Affecting Consequences</h5>
<ul><li>Fluid properties (toxicity, flammability, corrosivity)</li>
<li>Fluid inventory and release rate</li>
<li>Equipment size and operating pressure</li>
<li>Detection and isolation capabilities</li>
<li>Proximity to personnel and public</li>
<li>Environmental sensitivity of the area</li></ul>
<h5>Consequence Categories (API 581)</h5>
<table class="table table-bordered"><thead><tr><th>Category</th><th>Area (ft2)</th><th>Description</th></tr></thead>
<tbody>
<tr><td class="bg-success text-white">A</td><td>&lt; 100</td><td>Low consequence</td></tr>
<tr><td class="bg-info text-white">B</td><td>100 - 1,000</td><td>Medium-low</td></tr>
<tr><td class="bg-warning">C</td><td>1,000 - 3,000</td><td>Medium</td></tr>
<tr><td class="bg-danger text-white">D</td><td>3,000 - 10,000</td><td>Medium-high</td></tr>
<tr><td class="bg-dark text-white">E</td><td>&gt; 10,000</td><td>High consequence</td></tr>
</tbody></table>'],
            5 => ['title' => 'Probability Analysis', 'content' => '<h4>Probability Analysis</h4>
<p>Probability analysis determines the likelihood of equipment failure based on active damage mechanisms, inspection effectiveness, and equipment condition.</p>
<h5>Probability of Failure Components</h5>
<p>In API 581, the probability of failure is calculated using:</p>
<p class="text-center"><code>PoF = gff x D<sub>f</sub> x F<sub>MS</sub></code></p>
<ul><li><strong>gff:</strong> Generic Failure Frequency - baseline failure rate for equipment type</li>
<li><strong>D<sub>f</sub>:</strong> Damage Factor - adjustment based on active damage mechanisms</li>
<li><strong>F<sub>MS</sub>:</strong> Management Systems Factor - adjustment for management system effectiveness</li></ul>
<h5>Damage Factors</h5>
<p>Damage factors account for:</p>
<ul><li>Thinning (general and localized corrosion)</li>
<li>Stress Corrosion Cracking (SCC)</li>
<li>High Temperature Hydrogen Attack (HTHA)</li>
<li>External damage (CUI, atmospheric corrosion)</li>
<li>Brittle fracture</li>
<li>Mechanical fatigue</li></ul>
<h5>Inspection Effectiveness</h5>
<p>The effectiveness of past inspections directly affects the damage factor. Higher effectiveness inspections reduce uncertainty and lower the calculated PoF.</p>'],
            6 => ['title' => 'Risk Analysis and Management', 'content' => '<h4>Risk Analysis and Management</h4>
<p>Risk analysis combines probability and consequence assessments to determine the overall risk level of each equipment item.</p>
<h5>Risk Calculation</h5>
<p>Risk can be expressed as:</p>
<ul><li><strong>Risk Area:</strong> PoF x CoF area (ft2/year or m2/year)</li>
<li><strong>Financial Risk:</strong> PoF x Financial consequence ($/year)</li></ul>
<h5>Risk Matrix</h5>
<p>The 5x5 risk matrix is the primary visualization tool:</p>
<div class="table-responsive">
<table class="table table-bordered text-center" style="max-width:400px;">
<tr><td class="bg-danger text-white">VH</td><td class="bg-danger text-white">VH</td><td class="bg-danger text-white">VH</td><td class="bg-danger text-white">VH</td><td class="bg-danger text-white">VH</td></tr>
<tr><td class="bg-warning">H</td><td class="bg-danger text-white">VH</td><td class="bg-danger text-white">VH</td><td class="bg-danger text-white">VH</td><td class="bg-danger text-white">VH</td></tr>
<tr><td class="bg-warning">M</td><td class="bg-warning">MH</td><td class="bg-warning">H</td><td class="bg-danger text-white">VH</td><td class="bg-danger text-white">VH</td></tr>
<tr><td class="bg-success text-white">L</td><td class="bg-warning">M</td><td class="bg-warning">MH</td><td class="bg-warning">H</td><td class="bg-danger text-white">VH</td></tr>
<tr><td class="bg-success text-white">L</td><td class="bg-success text-white">L</td><td class="bg-warning">M</td><td class="bg-warning">MH</td><td class="bg-warning">H</td></tr>
</table>
</div>
<h5>Risk Management Actions</h5>
<ul><li><strong>Very High Risk:</strong> Immediate action required - reduce interval, additional inspection</li>
<li><strong>High Risk:</strong> Priority attention - enhance inspection program</li>
<li><strong>Medium Risk:</strong> Standard inspection program</li>
<li><strong>Low Risk:</strong> Extended intervals may be appropriate</li></ul>'],
            7 => ['title' => 'Inspection Planning', 'content' => '<h4>Inspection Planning</h4>
<p>The primary output of an RBI assessment is a risk-based inspection plan that optimizes inspection activities.</p>
<h5>Inspection Plan Elements</h5>
<ul><li>Inspection scope (what to inspect)</li>
<li>Inspection method (how to inspect)</li>
<li>Inspection interval (when to inspect)</li>
<li>Inspection locations (where to inspect)</li>
<li>Acceptance criteria</li></ul>
<h5>NDE Method Selection</h5>
<table class="table table-bordered"><thead class="table-dark"><tr><th>Damage Mechanism</th><th>Primary NDE</th><th>Supplemental NDE</th></tr></thead>
<tbody>
<tr><td>General thinning</td><td>UT thickness</td><td>Profile radiography</td></tr>
<tr><td>Localized corrosion</td><td>UT scanning</td><td>TOFD, Phased Array</td></tr>
<tr><td>SCC</td><td>WFMT, ACFM</td><td>UT shear wave, TOFD</td></tr>
<tr><td>HTHA</td><td>Advanced UT (AUBT)</td><td>Backscatter, TOFD</td></tr>
<tr><td>CUI</td><td>Profile RT</td><td>Pulsed eddy current, UT</td></tr>
</tbody></table>'],
            8 => ['title' => 'Risk Management', 'content' => '<h4>Risk Management</h4>
<p>Risk management in RBI involves ongoing activities to monitor, reduce, and manage risk levels across the facility.</p>
<h5>Risk Mitigation Strategies</h5>
<ol><li><strong>Inspection:</strong> Increase inspection frequency or effectiveness to reduce uncertainty</li>
<li><strong>Monitoring:</strong> Install continuous monitoring systems (corrosion probes, sensors)</li>
<li><strong>Process Changes:</strong> Modify operating conditions to reduce degradation rates</li>
<li><strong>Metallurgical Upgrades:</strong> Replace with more resistant materials</li>
<li><strong>Design Changes:</strong> Modify equipment design to reduce consequences</li>
<li><strong>Operational Controls:</strong> Implement procedures to prevent upset conditions</li></ol>
<h5>Program Maintenance</h5>
<p>An RBI program must be living and continuously updated:</p>
<ul><li>Reassess after each inspection</li><li>Update for process changes</li><li>Incorporate new damage mechanism knowledge</li><li>Review at each turnaround</li><li>Periodic comprehensive review (every 5 years minimum)</li></ul>
<div class="alert alert-success"><i class="fas fa-sync me-2"></i><strong>Continuous Improvement:</strong> The RBI program should become more accurate over time as more data is collected and incorporated into the risk models.</div>'],
        ],
        'quiz' => [
            ['q' => 'API 580 is applicable to which type of equipment?', 'options' => ['Rotating equipment', 'Fixed pressure equipment', 'Electrical equipment', 'All of the above'], 'answer' => 1],
            ['q' => 'The RBI team should include expertise in which area?', 'options' => ['Marketing', 'Corrosion engineering', 'Graphic design', 'Human resources'], 'answer' => 1],
            ['q' => 'Which is NOT a consequence category in RBI?', 'options' => ['Safety', 'Environmental', 'Aesthetic', 'Financial'], 'answer' => 2],
            ['q' => 'What does gff represent in the PoF equation?', 'options' => ['General failure factor', 'Generic failure frequency', 'Global fluid fraction', 'Ground fault finder'], 'answer' => 1],
            ['q' => 'How many categories are in the API 581 consequence rating?', 'options' => ['3', '4', '5', '6'], 'answer' => 2],
            ['q' => 'What is the primary output of an RBI assessment?', 'options' => ['Financial report', 'Inspection plan', 'Equipment order', 'Personnel evaluation'], 'answer' => 1],
            ['q' => 'Which NDE method is primary for detecting SCC?', 'options' => ['Ultrasonic thickness', 'Wet Fluorescent MT', 'Visual inspection', 'Radiographic testing'], 'answer' => 1],
            ['q' => 'How often should an RBI program have a comprehensive review?', 'options' => ['Every year', 'Every 3 years', 'Every 5 years minimum', 'Every 10 years'], 'answer' => 2],
            ['q' => 'API 580 is classified as:', 'options' => ['A code requirement', 'A recommended practice', 'A legal requirement', 'A government regulation'], 'answer' => 1],
            ['q' => 'Which factor adjusts PoF for management system quality?', 'options' => ['Damage Factor', 'Management Systems Factor', 'Correction Factor', 'Safety Factor'], 'answer' => 1],
            ['q' => 'Data validation should check for:', 'options' => ['Accuracy only', 'Completeness only', 'Accuracy, completeness, consistency, currency', 'Color and format'], 'answer' => 2],
            ['q' => 'Risk can be expressed in which unit?', 'options' => ['Pounds per square inch', 'Square feet per year', 'Gallons per minute', 'Degrees Fahrenheit'], 'answer' => 1],
            ['q' => 'CUI is best detected using:', 'options' => ['Visual inspection only', 'Profile radiography', 'Pressure testing', 'Chemical analysis'], 'answer' => 1],
            ['q' => 'A Very High risk rating requires:', 'options' => ['No action', 'Routine inspection', 'Immediate action', 'Annual review'], 'answer' => 2],
            ['q' => 'Which is a risk mitigation strategy?', 'options' => ['Ignoring the problem', 'Metallurgical upgrades', 'Reducing safety staff', 'Eliminating inspections'], 'answer' => 1],
        ]
    ],
    3 => [
        'title' => 'API 581 - RBI Methodology',
        'difficulty' => 'Advanced',
        'duration' => '6 hours',
        'icon' => 'fa-calculator',
        'color' => 'danger',
        'category' => 'API 580/581',
        'lessons' => [
            1 => ['title' => 'Thinning Damage Factor', 'content' => '<h4>Thinning Damage Factor</h4><p>The thinning damage factor is the most commonly used DF in API 581 and accounts for general and localized corrosion.</p><h5>Calculation Method</h5><p>The thinning DF depends on:</p><ul><li>Art parameter (ratio of measured thickness loss to corrosion allowance)</li><li>Number and effectiveness of inspections</li><li>Time in service</li><li>Corrosion rate (short-term and long-term)</li></ul><h5>Art Parameter</h5><p><code>Art = max(Age x CR / (t<sub>nom</sub> - t<sub>min</sub>), 0)</code></p><p>Where CR is the corrosion rate, t<sub>nom</sub> is nominal thickness, and t<sub>min</sub> is minimum required thickness.</p><h5>Inspection Effectiveness Categories</h5><table class="table table-bordered"><tr><th>Category</th><th>Description</th><th>Example</th></tr><tr><td>A - Highly Effective</td><td>High confidence in identifying damage</td><td>100% UT scanning with grid</td></tr><tr><td>B - Usually Effective</td><td>Good detection probability</td><td>Spot UT with radiography</td></tr><tr><td>C - Fairly Effective</td><td>Moderate detection</td><td>Limited spot UT readings</td></tr><tr><td>D - Poorly Effective</td><td>Low detection probability</td><td>External visual only</td></tr><tr><td>E - Ineffective</td><td>No meaningful inspection</td><td>No inspection performed</td></tr></table>'],
            2 => ['title' => 'Component Lining Damage Factor', 'content' => '<h4>Component Lining Damage Factor</h4><p>This DF applies to equipment with internal linings (refractory, metallic linings, coatings) that protect the base metal.</p><h5>Lining Types</h5><ul><li><strong>Metallic Linings:</strong> Clad, weld overlay, strip-lined vessels</li><li><strong>Non-metallic Linings:</strong> Refractory, glass, rubber, polymer coatings</li></ul><h5>Key Considerations</h5><ul><li>Lining condition and inspection history</li><li>Age of lining vs expected service life</li><li>Monitoring methods available</li><li>Consequence of lining failure (exposed base metal corrosion rate)</li></ul><p>The lining DF accounts for the probability that the lining has failed and the base metal is being attacked at the unprotected corrosion rate.</p><div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Loss of lining integrity can result in very rapid corrosion of the base metal, potentially leading to failure within one operating cycle.</div>'],
            3 => ['title' => 'External Damage Factor', 'content' => '<h4>External Damage Factor</h4><p>External damage factors address corrosion and cracking that occurs on the external surface of equipment.</p><h5>Types of External Damage</h5><ul><li><strong>CUI (Corrosion Under Insulation):</strong> Corrosion occurring under thermal insulation</li><li><strong>Atmospheric Corrosion:</strong> Corrosion from exposure to atmosphere</li><li><strong>ECSCC:</strong> External Chloride Stress Corrosion Cracking</li></ul><h5>CUI Critical Temperature Ranges</h5><table class="table table-bordered"><tr><th>Material</th><th>Temperature Range</th></tr><tr><td>Carbon Steel</td><td>25F to 350F (-4C to 175C)</td></tr><tr><td>Austenitic SS (300 series)</td><td>150F to 400F (65C to 205C) for ECSCC</td></tr></table><h5>Contributing Factors</h5><ul><li>Insulation type and condition</li><li>Climate and location</li><li>Coating condition</li><li>Temperature cycling</li><li>Proximity to moisture sources</li></ul>'],
            4 => ['title' => 'SCC Damage Factor', 'content' => '<h4>Stress Corrosion Cracking Damage Factor</h4><p>SCC damage factors address various forms of environmentally-assisted cracking.</p><h5>SCC Mechanisms Covered</h5><ul><li>Alkaline SCC (Caustic cracking)</li><li>Amine SCC</li><li>Sulfide Stress Cracking (SSC)</li><li>Hydrogen Induced Cracking (HIC/SOHIC)</li><li>Chloride SCC</li><li>Polythionic Acid SCC</li><li>Carbonate SCC</li></ul><h5>SCC DF Determination</h5><p>The SCC damage factor considers:</p><ol><li>Susceptibility of material/environment combination</li><li>Severity index (based on cracking propensity)</li><li>Inspection effectiveness and findings</li><li>PWHT status</li><li>Applied and residual stress levels</li></ol><div class="alert alert-danger"><i class="fas fa-skull-crossbones me-2"></i><strong>Critical:</strong> SCC can cause sudden failure without warning. Equipment susceptible to SCC requires careful monitoring and inspection with appropriate NDE methods.</div>'],
            5 => ['title' => 'HTHA Damage Factor', 'content' => '<h4>High Temperature Hydrogen Attack Damage Factor</h4><p>HTHA occurs in carbon and low-alloy steels exposed to hydrogen at elevated temperatures, causing decarburization and fissuring.</p><h5>HTHA Mechanism</h5><p>Hydrogen diffuses into steel at high temperatures and reacts with carbon to form methane (CH4). The methane molecules are too large to diffuse out, creating internal pressure that causes fissuring.</p><h5>Nelson Curves</h5><p>API 941 Nelson Curves define safe operating limits for various steels:</p><ul><li>Carbon steel: ~400F (204C) at high H2 partial pressure</li><li>1.25Cr-0.5Mo: Higher temperature capability</li><li>2.25Cr-1Mo: Even higher limits</li><li>Austenitic stainless: Generally immune</li></ul><h5>HTHA DF Factors</h5><ul><li>Distance from Nelson Curve</li><li>Time in service at HTHA conditions</li><li>Steel type and PWHT condition</li><li>Inspection methods and findings</li><li>Hydrogen partial pressure</li></ul>'],
            6 => ['title' => 'Brittle Fracture Damage Factor', 'content' => '<h4>Brittle Fracture Damage Factor</h4><p>The brittle fracture DF addresses the susceptibility of equipment to brittle failure at low temperatures.</p><h5>Key Concepts</h5><ul><li><strong>MDMT:</strong> Minimum Design Metal Temperature</li><li><strong>CET:</strong> Critical Exposure Temperature</li><li><strong>Impact Testing:</strong> Charpy V-notch test results</li></ul><h5>Risk Factors for Brittle Fracture</h5><ul><li>Operating below MDMT</li><li>Carbon steel without impact testing</li><li>Thick-walled equipment</li><li>Auto-refrigeration scenarios</li><li>Age-related temper embrittlement</li><li>Presence of crack-like flaws</li></ul><h5>Mitigation</h5><ul><li>Operating procedures to prevent cold pressurization</li><li>Minimum pressurization temperature procedures</li><li>Impact testing verification</li><li>PWHT for stress relief</li></ul>'],
            7 => ['title' => 'Consequence Analysis Methods', 'content' => '<h4>API 581 Consequence Analysis Methods</h4><p>API 581 provides two levels of consequence analysis: Level 1 (simplified) and Level 2 (detailed).</p><h5>Level 1 - Simplified Analysis</h5><p>Uses lookup tables and simplified calculations based on:</p><ul><li>Representative fluid groups</li><li>Equipment inventory categories</li><li>Detection and isolation system ratings</li></ul><h5>Level 2 - Detailed Analysis</h5><p>More rigorous analysis considering:</p><ul><li>Specific fluid properties</li><li>Actual equipment inventories</li><li>Dispersion modeling</li><li>Fire/explosion consequence modeling</li></ul><h5>Consequence Types</h5><table class="table table-bordered"><tr><th>Type</th><th>Description</th></tr><tr><td>Component Damage</td><td>Area of equipment damage from fire/explosion</td></tr><tr><td>Personnel Injury</td><td>Area where injuries may occur</td></tr><tr><td>Financial Loss</td><td>Equipment, production loss, cleanup costs</td></tr></table>'],
            8 => ['title' => 'Financial Risk Analysis', 'content' => '<h4>Financial Risk Analysis</h4><p>Financial risk analysis quantifies the economic impact of equipment failure to support business decisions.</p><h5>Financial Consequence Components</h5><ul><li><strong>Equipment Damage Cost:</strong> Repair or replacement of failed equipment</li><li><strong>Production Loss:</strong> Lost revenue during shutdown</li><li><strong>Environmental Cleanup:</strong> Cost of remediation</li><li><strong>Business Interruption:</strong> Lost revenue from downstream impacts</li><li><strong>Injury/Fatality Costs:</strong> Medical, legal, regulatory penalties</li></ul><h5>Financial Risk Calculation</h5><p><code>Financial Risk = PoF x Total Financial Consequence</code></p><h5>Cost-Benefit Analysis</h5><p>RBI enables comparison of inspection costs vs risk reduction:</p><ul><li>Cost of inspection activities</li><li>Expected risk reduction from inspection</li><li>Net present value of risk mitigation</li><li>Optimal inspection strategy selection</li></ul><div class="alert alert-info"><i class="fas fa-dollar-sign me-2"></i><strong>ROI:</strong> A well-implemented RBI program typically shows positive ROI within the first year through optimized inspection spending and reduced unplanned downtime.</div>'],
        ],
        'quiz' => [
            ['q' => 'What parameter is central to the thinning damage factor?', 'options' => ['Brt', 'Art', 'Crt', 'Drt'], 'answer' => 1],
            ['q' => 'Category A inspection effectiveness means:', 'options' => ['No inspection', 'Poorly effective', 'Usually effective', 'Highly effective'], 'answer' => 3],
            ['q' => 'CUI is most critical in which temperature range for carbon steel?', 'options' => ['-50F to 0F', '25F to 350F', '400F to 800F', '800F to 1200F'], 'answer' => 1],
            ['q' => 'Which SCC mechanism involves caustic solutions?', 'options' => ['SSC', 'Alkaline SCC', 'Chloride SCC', 'PTA SCC'], 'answer' => 1],
            ['q' => 'HTHA is caused by reaction of hydrogen with:', 'options' => ['Oxygen', 'Nitrogen', 'Carbon', 'Sulfur'], 'answer' => 2],
            ['q' => 'MDMT stands for:', 'options' => ['Maximum Design Metal Thickness', 'Minimum Design Metal Temperature', 'Maximum Damage Mechanism Type', 'Minimum Damage Measurement Threshold'], 'answer' => 1],
            ['q' => 'API 581 Level 1 consequence analysis uses:', 'options' => ['CFD simulation', 'Lookup tables', 'Field measurements', 'Monte Carlo simulation'], 'answer' => 1],
            ['q' => 'Financial risk is calculated as:', 'options' => ['CoF / PoF', 'PoF + CoF', 'PoF x Financial CoF', 'PoF - Financial CoF'], 'answer' => 2],
            ['q' => 'Nelson Curves are referenced in which API standard?', 'options' => ['API 510', 'API 570', 'API 941', 'API 653'], 'answer' => 2],
            ['q' => 'Component lining failure can result in:', 'options' => ['Improved corrosion resistance', 'Rapid base metal corrosion', 'Lower operating costs', 'Extended equipment life'], 'answer' => 1],
            ['q' => 'External CLSCC occurs in which material?', 'options' => ['Carbon steel', 'Austenitic stainless steel', 'Aluminum', 'Copper'], 'answer' => 1],
            ['q' => 'The damage factor is used to adjust:', 'options' => ['Consequence of failure', 'Generic failure frequency', 'Operating pressure', 'Wall thickness'], 'answer' => 1],
            ['q' => 'Auto-refrigeration is a concern for:', 'options' => ['Thinning', 'SCC', 'Brittle fracture', 'HTHA'], 'answer' => 2],
            ['q' => 'Production loss in financial CoF includes:', 'options' => ['Inspection costs only', 'Lost revenue during shutdown', 'Insurance premiums', 'Training costs'], 'answer' => 1],
            ['q' => 'How many SCC mechanisms are covered in API 581?', 'options' => ['3', '5', '7+', '2'], 'answer' => 2],
            ['q' => 'Which material is generally immune to HTHA?', 'options' => ['Carbon steel', '1Cr-0.5Mo', '2.25Cr-1Mo', 'Austenitic stainless'], 'answer' => 3],
            ['q' => 'Temper embrittlement is a concern for brittle fracture in:', 'options' => ['New equipment only', 'Aged Cr-Mo steels', 'Aluminum alloys', 'Copper alloys'], 'answer' => 1],
            ['q' => 'Level 2 consequence analysis includes:', 'options' => ['Simplified tables only', 'Dispersion modeling', 'No calculations', 'Visual inspection only'], 'answer' => 1],
            ['q' => 'F_MS adjusts PoF for:', 'options' => ['Material strength', 'Management systems', 'Fluid service', 'Equipment size'], 'answer' => 1],
            ['q' => 'A positive ROI from RBI is typically seen within:', 'options' => ['5 years', '3 years', 'The first year', '10 years'], 'answer' => 2],
        ]
    ],
    4 => [
        'title' => 'Damage Mechanism Identification',
        'difficulty' => 'Intermediate',
        'duration' => '3 hours',
        'icon' => 'fa-bolt',
        'color' => 'warning',
        'category' => 'Damage Mechanisms',
        'lessons' => [
            1 => ['title' => 'Corrosion Mechanisms Overview', 'content' => '<h4>Corrosion Mechanisms Overview</h4><p>Corrosion is the degradation of a material due to interaction with its environment. In RBI, identifying active damage mechanisms is crucial.</p><h5>Classification of Corrosion</h5><ul><li><strong>General (Uniform) Corrosion:</strong> Even material loss across the surface</li><li><strong>Localized Corrosion:</strong> Concentrated attack (pitting, crevice, MIC)</li><li><strong>Galvanic Corrosion:</strong> Dissimilar metal contact in electrolyte</li><li><strong>Flow-Assisted:</strong> Erosion-corrosion, flow-accelerated corrosion</li></ul><h5>Key Reference: API 571</h5><p>API 571 "Damage Mechanisms Affecting Fixed Equipment in the Refining Industry" catalogs over 60 damage mechanisms with descriptions, affected materials, and critical factors.</p>'],
            2 => ['title' => 'Stress Corrosion Cracking', 'content' => '<h4>Stress Corrosion Cracking (SCC)</h4><p>SCC requires three simultaneous conditions: susceptible material, corrosive environment, and tensile stress.</p><h5>Common SCC Mechanisms in Refineries</h5><table class="table table-bordered"><tr><th>Mechanism</th><th>Material</th><th>Environment</th></tr><tr><td>Caustic SCC</td><td>Carbon steel</td><td>Caustic (NaOH) above 150F</td></tr><tr><td>Amine SCC</td><td>Carbon steel</td><td>Lean amine solutions</td></tr><tr><td>Chloride SCC</td><td>Austenitic SS</td><td>Chloride + temperature + moisture</td></tr><tr><td>SSC/HIC</td><td>Carbon/low alloy</td><td>Wet H2S environments</td></tr><tr><td>Polythionic Acid</td><td>Sensitized SS</td><td>Sulfur oxides + moisture</td></tr></table><h5>Prevention</h5><ul><li>Material selection appropriate for service</li><li>Post-weld heat treatment (PWHT)</li><li>Control of environmental conditions</li><li>Proper shutdown/startup procedures</li></ul>'],
            3 => ['title' => 'High Temperature Mechanisms', 'content' => '<h4>High Temperature Damage Mechanisms</h4><h5>Oxidation</h5><p>Reaction of metal surface with oxygen at elevated temperatures. Carbon steel begins to experience significant oxidation above 900F (482C).</p><h5>Sulfidation</h5><p>Corrosion by sulfur compounds at high temperatures (above 500F). Rate depends on sulfur content, temperature, and alloy composition.</p><h5>Carburization</h5><p>Absorption of carbon into steel at high temperatures, leading to embrittlement. Common in ethylene furnace tubes.</p><h5>Creep</h5><p>Slow, progressive deformation under sustained stress at elevated temperature. Key concern for equipment operating above:</p><ul><li>Carbon steel: 700F (370C)</li><li>Cr-Mo steels: 750-900F depending on grade</li><li>Austenitic SS: 1000F (538C)</li></ul><h5>High Temperature Hydrogen Attack (HTHA)</h5><p>Internal decarburization and fissuring from hydrogen diffusion at elevated temperatures.</p>'],
            4 => ['title' => 'Hydrogen Damage', 'content' => '<h4>Hydrogen Damage Mechanisms</h4><h5>Wet H2S Damage</h5><ul><li><strong>Hydrogen Blistering:</strong> Atomic hydrogen diffuses into steel and recombines at inclusions, forming blisters</li><li><strong>HIC:</strong> Hydrogen-Induced Cracking - stepwise internal cracking linking blisters</li><li><strong>SOHIC:</strong> Stress-Oriented HIC - stacking of HIC cracks in stress direction</li><li><strong>SSC:</strong> Sulfide Stress Cracking - cracking under stress in H2S environment</li></ul><h5>High Temperature Hydrogen Attack</h5><p>Occurs above ~400F at elevated H2 partial pressures. Uses Nelson Curves (API 941) to determine susceptibility.</p><h5>Hydrogen Embrittlement</h5><p>Loss of ductility due to dissolved hydrogen. Most severe in high-strength steels.</p><div class="alert alert-danger"><i class="fas fa-radiation me-2"></i>Hydrogen damage can cause sudden, catastrophic failure. Equipment in hydrogen service requires specialized inspection and monitoring programs.</div>'],
            5 => ['title' => 'Erosion and Fatigue', 'content' => '<h4>Erosion and Fatigue Mechanisms</h4><h5>Erosion</h5><ul><li><strong>Solid Particle Erosion:</strong> Material removal by impacting particles (catalyst, sand)</li><li><strong>Liquid Droplet Erosion:</strong> Damage from impacting liquid droplets (wet steam)</li><li><strong>Erosion-Corrosion:</strong> Combined mechanical and chemical attack</li><li><strong>Cavitation:</strong> Damage from collapsing vapor bubbles</li></ul><h5>Fatigue</h5><ul><li><strong>Mechanical Fatigue:</strong> Cyclic loading causing crack initiation and growth</li><li><strong>Thermal Fatigue:</strong> Cyclic temperature changes causing stress cycles</li><li><strong>Corrosion Fatigue:</strong> Fatigue in a corrosive environment (reduced fatigue life)</li><li><strong>Vibration Fatigue:</strong> High-frequency cyclic loading from vibration</li></ul><h5>Critical Locations</h5><p>Fatigue is most critical at:</p><ul><li>Nozzle connections and welds</li><li>Support attachments</li><li>Piping bends and branch connections</li><li>Areas of geometric stress concentration</li></ul>'],
            6 => ['title' => 'Environmental Cracking', 'content' => '<h4>Environmental Cracking</h4><p>Environmental cracking encompasses mechanisms where the environment contributes to crack initiation and growth.</p><h5>Corrosion Fatigue</h5><p>Combination of cyclic stress and corrosive environment. Reduces the fatigue endurance limit compared to non-corrosive conditions.</p><h5>Liquid Metal Embrittlement (LME)</h5><p>Cracking caused by contact with low-melting-point metals (mercury, zinc, copper). Can cause sudden brittle failure.</p><h5>Soil-Side Corrosion</h5><p>Corrosion of buried or ground-contact equipment. Influenced by soil resistivity, pH, moisture, and cathodic protection.</p><h5>Microbiologically Influenced Corrosion (MIC)</h5><p>Corrosion accelerated by microorganisms. Common in:</p><ul><li>Water systems and cooling towers</li><li>Hydrotest water left in equipment</li><li>Stagnant areas with low flow</li><li>Dead legs in piping systems</li></ul><div class="alert alert-info"><i class="fas fa-microscope me-2"></i>MIC can cause very high localized corrosion rates (up to 100+ mpy) and is often overlooked in traditional corrosion assessments.</div>'],
        ],
        'quiz' => [
            ['q' => 'How many damage mechanisms does API 571 catalog?', 'options' => ['About 20', 'About 40', 'Over 60', 'Over 100'], 'answer' => 2],
            ['q' => 'SCC requires which three simultaneous conditions?', 'options' => ['Heat, pressure, flow', 'Material, environment, stress', 'Acid, base, salt', 'Oxygen, water, metal'], 'answer' => 1],
            ['q' => 'Sulfidation becomes significant above what temperature?', 'options' => ['200F', '350F', '500F', '800F'], 'answer' => 2],
            ['q' => 'Hydrogen blistering occurs in which environment?', 'options' => ['Dry air', 'Wet H2S', 'Pure oxygen', 'Inert gas'], 'answer' => 1],
            ['q' => 'Which is NOT a type of erosion?', 'options' => ['Solid particle', 'Cavitation', 'Galvanic erosion', 'Liquid droplet'], 'answer' => 2],
            ['q' => 'Creep is a concern for carbon steel above:', 'options' => ['200F', '500F', '700F', '1000F'], 'answer' => 2],
            ['q' => 'MIC stands for:', 'options' => ['Minimum Inspection Criteria', 'Microbiologically Influenced Corrosion', 'Maximum Impact Coefficient', 'Metal Ion Concentration'], 'answer' => 1],
            ['q' => 'Polythionic acid SCC affects which material?', 'options' => ['Carbon steel', 'Aluminum', 'Sensitized stainless steel', 'Copper'], 'answer' => 2],
            ['q' => 'HIC cracking propagates in what pattern?', 'options' => ['Circular', 'Stepwise/staircase', 'Spiral', 'Random'], 'answer' => 1],
            ['q' => 'Thermal fatigue is caused by:', 'options' => ['Constant temperature', 'Cyclic temperature changes', 'Low temperature only', 'High pressure'], 'answer' => 1],
            ['q' => 'Nelson Curves are used for which mechanism?', 'options' => ['CUI', 'HTHA', 'SCC', 'MIC'], 'answer' => 1],
            ['q' => 'Liquid Metal Embrittlement can be caused by:', 'options' => ['Iron', 'Mercury', 'Nickel', 'Chromium'], 'answer' => 1],
            ['q' => 'Caustic SCC occurs above what temperature?', 'options' => ['100F', '150F', '250F', '400F'], 'answer' => 1],
            ['q' => 'Dead legs are susceptible to:', 'options' => ['Creep', 'MIC', 'HTHA', 'Oxidation'], 'answer' => 1],
            ['q' => 'Carburization is most common in:', 'options' => ['Storage tanks', 'Ethylene furnace tubes', 'Cooling water piping', 'Underground pipelines'], 'answer' => 1],
        ]
    ],
    5 => [
        'title' => 'Inspection Techniques',
        'difficulty' => 'Intermediate',
        'duration' => '3 hours',
        'icon' => 'fa-search',
        'color' => 'info',
        'category' => 'Inspection Techniques',
        'lessons' => [
            1 => ['title' => 'Visual Inspection (VT)', 'content' => '<h4>Visual Inspection (VT)</h4><p>Visual inspection is the most fundamental and widely used inspection method. It can detect surface conditions, leaks, corrosion products, and structural anomalies.</p><h5>Types of Visual Inspection</h5><ul><li><strong>Direct VT:</strong> Unaided or aided with magnification, mirrors, lighting</li><li><strong>Remote VT:</strong> Using cameras, borescopes, drones, crawlers</li></ul><h5>What VT Can Detect</h5><ul><li>Surface corrosion and pitting</li><li>Cracks visible at the surface</li><li>Distortion, bulging, sagging</li><li>Coating/lining damage</li><li>Insulation condition</li><li>Leaks and seepage</li><li>Misalignment</li></ul><h5>Limitations</h5><ul><li>Cannot detect subsurface defects</li><li>Cannot measure wall thickness</li><li>Dependent on inspector skill and conditions</li><li>Surface preparation required for crack detection</li></ul>'],
            2 => ['title' => 'Ultrasonic Testing (UT)', 'content' => '<h4>Ultrasonic Testing (UT)</h4><p>UT uses high-frequency sound waves to measure wall thickness and detect internal defects.</p><h5>UT Methods</h5><table class="table table-bordered"><tr><th>Method</th><th>Application</th></tr><tr><td>Compression Wave UT</td><td>Wall thickness measurement</td></tr><tr><td>Shear Wave UT</td><td>Weld inspection, crack detection</td></tr><tr><td>Phased Array UT (PAUT)</td><td>Advanced scanning with multiple angles</td></tr><tr><td>TOFD</td><td>Crack sizing and monitoring</td></tr><tr><td>Automated UT (AUT)</td><td>High-speed scanning of large areas</td></tr><tr><td>Long Range UT (LRUT)</td><td>Screening of piping over long distances</td></tr></table><h5>Advantages</h5><ul><li>Accurate thickness measurements (0.001" resolution)</li><li>Can detect subsurface flaws</li><li>Single-sided access required</li><li>No radiation hazard</li><li>Portable equipment available</li></ul><h5>Limitations</h5><ul><li>Requires trained operators</li><li>Surface preparation needed</li><li>Couplant required</li><li>Material-dependent calibration</li></ul>'],
            3 => ['title' => 'Radiographic Testing (RT)', 'content' => '<h4>Radiographic Testing (RT)</h4><p>RT uses X-rays or gamma rays to create an image of the internal structure of a component.</p><h5>RT Sources</h5><ul><li><strong>X-ray:</strong> Electrically generated, adjustable energy, higher image quality</li><li><strong>Gamma ray (Ir-192, Co-60):</strong> Radioactive source, portable, no electricity needed</li></ul><h5>Applications in RBI</h5><ul><li>Weld inspection</li><li>Profile radiography for thickness/CUI detection</li><li>Internal corrosion mapping</li><li>Detection of internal deposits</li></ul><h5>Digital Radiography (DR/CR)</h5><p>Modern digital methods offer advantages over film:</p><ul><li>Immediate results (no film processing)</li><li>Digital archiving and analysis</li><li>Enhanced image processing</li><li>Reduced radiation exposure times</li></ul><div class="alert alert-warning"><i class="fas fa-radiation me-2"></i><strong>Safety:</strong> RT requires radiation safety measures including controlled areas, dosimetry, and certified personnel.</div>'],
            4 => ['title' => 'Magnetic Particle Testing (MT)', 'content' => '<h4>Magnetic Particle Testing (MT)</h4><p>MT detects surface and near-surface discontinuities in ferromagnetic materials using magnetic fields and fine particles.</p><h5>Methods</h5><ul><li><strong>Dry Powder:</strong> Used at high temperatures or on rough surfaces</li><li><strong>Wet Fluorescent (WFMT):</strong> Highest sensitivity, used in darkened conditions</li><li><strong>Electromagnetic Yoke:</strong> Portable, most common field method</li></ul><h5>Applications</h5><ul><li>Weld inspection (surface cracks)</li><li>SCC detection (wet H2S, caustic, amine)</li><li>Fatigue crack detection</li><li>In-service inspection of nozzle welds</li></ul><h5>Key Considerations</h5><ul><li>Only works on ferromagnetic materials (carbon/low alloy steel)</li><li>Requires surface preparation (cleaning, coating removal)</li><li>Two magnetization directions needed for full coverage</li><li>Demagnetization may be required after inspection</li></ul>'],
            5 => ['title' => 'Penetrant Testing (PT)', 'content' => '<h4>Penetrant Testing (PT)</h4><p>PT detects surface-breaking discontinuities by applying a liquid penetrant that seeps into flaws and is revealed by a developer.</p><h5>PT Process</h5><ol><li>Surface preparation (cleaning)</li><li>Penetrant application and dwell time</li><li>Excess penetrant removal</li><li>Developer application</li><li>Examination and interpretation</li><li>Post-cleaning</li></ol><h5>Types</h5><ul><li><strong>Visible (color contrast):</strong> Red dye, white developer, viewed in white light</li><li><strong>Fluorescent:</strong> Fluorescent penetrant, viewed under UV/black light (higher sensitivity)</li></ul><h5>Advantages</h5><ul><li>Works on all non-porous materials (metals, ceramics, plastics)</li><li>Simple and inexpensive</li><li>Portable</li><li>High sensitivity for surface cracks</li></ul><h5>Limitations</h5><ul><li>Surface-breaking defects only</li><li>Requires good surface preparation</li><li>Temperature limitations of chemicals</li><li>Cannot be used on porous materials</li></ul>'],
            6 => ['title' => 'Advanced NDE Methods', 'content' => '<h4>Advanced NDE Methods</h4><h5>Time of Flight Diffraction (TOFD)</h5><p>Uses diffracted signals from crack tips for accurate sizing. Considered the most accurate method for crack depth measurement.</p><h5>Phased Array UT (PAUT)</h5><p>Multiple UT elements electronically controlled to steer and focus the beam. Provides real-time cross-sectional images (S-scans).</p><h5>Guided Wave Testing (GWT/LRUT)</h5><p>Screens long lengths of pipe from a single location. Can detect corrosion and other wall loss over distances of 30m+.</p><h5>Acoustic Emission (AE)</h5><p>Passive technique that detects stress waves from active defects. Used for in-service monitoring and leak detection.</p><h5>Eddy Current Testing (ECT)</h5><p>Electromagnetic method for surface and near-surface flaw detection. Commonly used for heat exchanger tube inspection.</p><h5>Comparison Table</h5><table class="table table-bordered table-sm"><tr><th>Method</th><th>Best For</th><th>Coverage</th></tr><tr><td>TOFD</td><td>Crack sizing</td><td>Localized</td></tr><tr><td>PAUT</td><td>Weld inspection, corrosion mapping</td><td>Moderate</td></tr><tr><td>GWT</td><td>Screening piping/pipelines</td><td>Wide (30m+)</td></tr><tr><td>AE</td><td>Active flaw monitoring</td><td>Wide (structure)</td></tr><tr><td>ECT</td><td>Tube inspection, coating assessment</td><td>Moderate</td></tr></table>'],
        ],
        'quiz' => [
            ['q' => 'Which is the most fundamental inspection method?', 'options' => ['UT', 'RT', 'Visual inspection', 'MT'], 'answer' => 2],
            ['q' => 'UT thickness measurements have what typical resolution?', 'options' => ['0.1 inch', '0.01 inch', '0.001 inch', '1 inch'], 'answer' => 2],
            ['q' => 'Gamma ray sources used in RT include:', 'options' => ['Uranium-235', 'Iridium-192', 'Carbon-14', 'Oxygen-16'], 'answer' => 1],
            ['q' => 'MT works only on which type of material?', 'options' => ['Non-magnetic', 'Ferromagnetic', 'Non-metallic', 'All materials'], 'answer' => 1],
            ['q' => 'PT can detect which type of defects?', 'options' => ['Subsurface only', 'Surface-breaking only', 'All defects', 'Internal only'], 'answer' => 1],
            ['q' => 'TOFD is best known for:', 'options' => ['Thickness measurement', 'Crack sizing accuracy', 'Visual inspection', 'Chemical analysis'], 'answer' => 1],
            ['q' => 'GWT can screen pipe over what distance?', 'options' => ['1 meter', '5 meters', '30+ meters', '1 kilometer'], 'answer' => 2],
            ['q' => 'WFMT stands for:', 'options' => ['Wet Frequency Modulation Test', 'Wet Fluorescent Magnetic Testing', 'Wire Feed Mechanical Test', 'Wide Field Measurement Technique'], 'answer' => 1],
            ['q' => 'Phased Array UT uses:', 'options' => ['Single element probe', 'Multiple electronically controlled elements', 'X-ray source', 'Magnetic particles'], 'answer' => 1],
            ['q' => 'Profile radiography is used to detect:', 'options' => ['Electrical faults', 'CUI and thickness loss', 'Temperature changes', 'Flow rates'], 'answer' => 1],
            ['q' => 'ECT is commonly used for:', 'options' => ['Pipeline inspection', 'Heat exchanger tube inspection', 'Tank floor scanning', 'Concrete testing'], 'answer' => 1],
            ['q' => 'AE testing is a ___ technique:', 'options' => ['Active/transmit', 'Passive/listening', 'Destructive', 'Chemical'], 'answer' => 1],
            ['q' => 'Fluorescent PT requires:', 'options' => ['White light only', 'UV/black light', 'X-rays', 'Magnetic field'], 'answer' => 1],
            ['q' => 'Digital radiography advantages include:', 'options' => ['Higher radiation dose', 'Film processing required', 'Immediate results', 'Lower image quality'], 'answer' => 2],
            ['q' => 'VT cannot measure:', 'options' => ['Surface corrosion', 'Leaks', 'Wall thickness', 'Coating damage'], 'answer' => 2],
        ]
    ],
    6 => [
        'title' => 'Using RBI Engineering Suite',
        'difficulty' => 'Beginner',
        'duration' => '2 hours',
        'icon' => 'fa-laptop',
        'color' => 'success',
        'category' => 'Software Usage',
        'lessons' => [
            1 => ['title' => 'System Overview & Navigation', 'content' => '<h4>System Overview & Navigation</h4><p>The RBI Engineering Suite provides a comprehensive platform for managing Risk-Based Inspection programs.</p><h5>Main Navigation</h5><ul><li><strong>Dashboard:</strong> Overview of key metrics, risk distribution, and upcoming activities</li><li><strong>Asset Management:</strong> Equipment registry, hierarchy, and corrosion circuits</li><li><strong>Risk Assessment:</strong> RBI assessments, risk matrix, and risk rankings</li><li><strong>Inspection Planning:</strong> Plans, schedules, and task management</li><li><strong>Analytics:</strong> Remaining life, corrosion rates, predictive analytics</li><li><strong>Integrations:</strong> SAP PM, Maximo, OSIsoft PI connections</li><li><strong>Reports:</strong> Configurable reporting engine</li><li><strong>Admin:</strong> User management, settings, audit log</li></ul><h5>Key Features</h5><ul><li>Responsive design works on desktop, tablet, and mobile</li><li>Dark sidebar with collapsible navigation</li><li>Global search (Ctrl+K)</li><li>Real-time notifications</li><li>PWA support for offline access</li></ul>'],
            2 => ['title' => 'Asset Management', 'content' => '<h4>Asset Management Module</h4><h5>Asset Hierarchy</h5><p>Organize equipment in a logical tree structure:</p><ul><li>Site &rarr; Plant &rarr; Unit &rarr; System &rarr; Equipment &rarr; Component</li></ul><h5>Asset Registry</h5><p>Each asset record contains:</p><ul><li>Equipment identification (tag number, description)</li><li>Design data (material, thickness, dimensions, MAWP)</li><li>Operating conditions (pressure, temperature, fluid)</li><li>Installation date and service history</li><li>Current risk level and inspection status</li></ul><h5>Corrosion Circuits</h5><p>Group equipment with similar corrosion environments:</p><ul><li>Same process fluid exposure</li><li>Similar operating conditions</li><li>Common damage mechanisms</li></ul>'],
            3 => ['title' => 'Running Risk Assessments', 'content' => '<h4>Running Risk Assessments</h4><h5>Assessment Workflow</h5><ol><li>Select equipment or circuit for assessment</li><li>Review and update design/operating data</li><li>Identify active damage mechanisms</li><li>Assign corrosion rates and damage factors</li><li>Calculate probability of failure</li><li>Evaluate consequences of failure</li><li>Generate risk results and risk matrix plot</li><li>Develop inspection recommendations</li></ol><h5>Assessment Types</h5><ul><li><strong>Qualitative:</strong> Screening-level using expert judgment</li><li><strong>Semi-quantitative:</strong> Scoring system with defined criteria</li><li><strong>Quantitative:</strong> Full API 581 methodology with calculated damage factors</li></ul>'],
            4 => ['title' => 'Inspection Planning', 'content' => '<h4>Inspection Planning Module</h4><h5>Plan Generation</h5><p>Based on RBI assessment results, the system generates:</p><ul><li>Recommended inspection intervals</li><li>NDE method selection based on damage mechanisms</li><li>Inspection scope and extent</li><li>CML locations and measurements required</li></ul><h5>Schedule Management</h5><ul><li>Calendar view of upcoming inspections</li><li>Turnaround planning support</li><li>Resource allocation</li><li>Task assignment and tracking</li></ul><h5>Field Execution</h5><ul><li>Mobile-friendly inspection forms</li><li>Photo documentation</li><li>Thickness reading entry</li><li>Real-time sync with desktop</li></ul>'],
            5 => ['title' => 'Analytics & Reporting', 'content' => '<h4>Analytics & Reporting</h4><h5>Analytics Modules</h5><ul><li><strong>Remaining Life:</strong> Calculate time to minimum thickness based on corrosion rates</li><li><strong>Corrosion Rate Trending:</strong> Track and analyze thickness measurement trends</li><li><strong>Sensitivity Analysis:</strong> Evaluate impact of parameter changes on risk</li><li><strong>Financial Risk:</strong> Quantify economic exposure and optimize inspection spending</li><li><strong>Predictive Analytics:</strong> ML-powered failure prediction models</li></ul><h5>Report Generation</h5><ul><li>Standard RBI assessment reports</li><li>Risk ranking reports</li><li>Inspection due lists</li><li>Management dashboards</li><li>Regulatory compliance reports</li><li>Export to PDF, Excel, CSV</li></ul>'],
            6 => ['title' => 'Integration Setup', 'content' => '<h4>Integration Setup</h4><h5>Available Integrations</h5><ul><li><strong>SAP PM:</strong> Bidirectional sync of equipment, work orders, and notifications</li><li><strong>IBM Maximo:</strong> Asset data exchange and work management</li><li><strong>OSIsoft PI:</strong> Real-time process data integration for corrosion monitoring</li></ul><h5>Configuration Steps</h5><ol><li>Navigate to Integrations &rarr; Integration Hub</li><li>Select the target system</li><li>Configure connection parameters (server, credentials, mapping)</li><li>Test connection</li><li>Define sync rules and schedules</li><li>Enable and monitor</li></ol><h5>IoT Integration</h5><p>Connect IoT sensors for real-time monitoring:</p><ul><li>Ultrasonic thickness sensors</li><li>Corrosion probes</li><li>Environmental sensors (temperature, humidity)</li><li>Acoustic emission monitors</li></ul>'],
        ],
        'quiz' => [
            ['q' => 'What keyboard shortcut opens global search?', 'options' => ['Ctrl+F', 'Ctrl+K', 'Ctrl+S', 'Ctrl+G'], 'answer' => 1],
            ['q' => 'The asset hierarchy goes from:', 'options' => ['Component to Site', 'Site to Component', 'Random order', 'Alphabetical'], 'answer' => 1],
            ['q' => 'How many assessment types does the system support?', 'options' => ['1', '2', '3', '4'], 'answer' => 2],
            ['q' => 'Which integration provides real-time process data?', 'options' => ['SAP PM', 'IBM Maximo', 'OSIsoft PI', 'Microsoft Excel'], 'answer' => 2],
            ['q' => 'Corrosion circuits group equipment by:', 'options' => ['Color', 'Size only', 'Similar corrosion environments', 'Alphabetical order'], 'answer' => 2],
            ['q' => 'The system supports which mobile feature?', 'options' => ['PWA for offline access', 'Virtual reality', 'Augmented reality', 'Voice commands only'], 'answer' => 0],
            ['q' => 'Reports can be exported to:', 'options' => ['PDF only', 'PDF, Excel, CSV', 'Paper only', 'No export available'], 'answer' => 1],
            ['q' => 'Predictive analytics uses:', 'options' => ['Manual calculations', 'Random guessing', 'ML-powered models', 'Coin flipping'], 'answer' => 2],
            ['q' => 'Financial Risk module helps:', 'options' => ['Tax filing', 'Quantify economic exposure', 'Payroll management', 'Inventory ordering'], 'answer' => 1],
            ['q' => 'The dashboard shows:', 'options' => ['Only text', 'Key metrics, risk distribution, upcoming activities', 'Weather forecast', 'Stock prices'], 'answer' => 1],
        ]
    ],
    7 => [
        'title' => 'Corrosion Rate Analysis',
        'difficulty' => 'Advanced',
        'duration' => '3 hours',
        'icon' => 'fa-chart-line',
        'color' => 'info',
        'category' => 'Damage Mechanisms',
        'lessons' => [
            1 => ['title' => 'Short-term vs Long-term Rates', 'content' => '<h4>Short-term vs Long-term Corrosion Rates</h4><h5>Long-term Corrosion Rate</h5><p>Calculated from the original/baseline thickness to the most recent measurement:</p><p><code>CR<sub>LT</sub> = (t<sub>original</sub> - t<sub>current</sub>) / Years in Service</code></p><h5>Short-term Corrosion Rate</h5><p>Calculated between the two most recent measurements:</p><p><code>CR<sub>ST</sub> = (t<sub>previous</sub> - t<sub>current</sub>) / (Date<sub>current</sub> - Date<sub>previous</sub>)</code></p><h5>Which Rate to Use?</h5><table class="table table-bordered"><tr><th>Scenario</th><th>Recommended Rate</th></tr><tr><td>Stable process conditions</td><td>Long-term rate</td></tr><tr><td>Recent process changes</td><td>Short-term rate</td></tr><tr><td>Limited data (1 reading)</td><td>Estimated rate from similar service</td></tr><tr><td>Multiple readings available</td><td>Maximum of ST and LT, or statistical analysis</td></tr></table>'],
            2 => ['title' => 'Statistical Analysis of Thickness Data', 'content' => '<h4>Statistical Analysis of Thickness Data</h4><h5>Data Requirements</h5><p>For meaningful statistical analysis, you need:</p><ul><li>Minimum 3-5 thickness readings at each CML</li><li>Consistent measurement locations</li><li>Known measurement uncertainty</li><li>Documented process changes</li></ul><h5>Statistical Methods</h5><ul><li><strong>Linear Regression:</strong> Fit a line through thickness vs time data to determine corrosion rate and predict future thickness</li><li><strong>Bayesian Analysis:</strong> Incorporate prior knowledge and update with new measurements</li><li><strong>Extreme Value Analysis:</strong> Analyze minimum readings to assess worst-case locations</li></ul><h5>Confidence Intervals</h5><p>Statistical analysis provides confidence bounds on corrosion rate and remaining life predictions, enabling risk-informed decisions.</p>'],
            3 => ['title' => 'Remaining Life Calculations', 'content' => '<h4>Remaining Life Calculations</h4><h5>Basic Remaining Life</h5><p><code>RL = (t<sub>actual</sub> - t<sub>required</sub>) / CR</code></p><p>Where:</p><ul><li>t<sub>actual</sub> = Current measured minimum thickness</li><li>t<sub>required</sub> = Minimum required thickness per code</li><li>CR = Corrosion rate (mpy or mm/yr)</li></ul><h5>Retirement Thickness</h5><p>Minimum required thickness is determined by:</p><ul><li>Pressure design calculations (ASME code)</li><li>Structural minimum for external loads</li><li>Code-specified minimum (e.g., API 653 for tanks)</li></ul><h5>Half-Life Concept</h5><p>When RL reaches half the inspection interval, it is time to re-inspect. This ensures adequate warning before reaching minimum thickness.</p><h5>Factors Affecting Accuracy</h5><ul><li>Measurement uncertainty</li><li>Corrosion rate variability</li><li>Process condition changes</li><li>Localized vs general corrosion</li></ul>'],
            4 => ['title' => 'Predictive Analytics', 'content' => '<h4>Predictive Analytics for Corrosion</h4><h5>Machine Learning Approaches</h5><ul><li><strong>Regression Models:</strong> Predict future thickness based on historical trends and operating conditions</li><li><strong>Random Forests:</strong> Identify key factors driving corrosion rates</li><li><strong>Neural Networks:</strong> Model complex non-linear relationships</li><li><strong>Time Series Forecasting:</strong> ARIMA, LSTM models for temporal prediction</li></ul><h5>Input Features</h5><p>Predictive models can incorporate:</p><ul><li>Historical thickness data</li><li>Process parameters (temperature, pressure, composition)</li><li>Environmental conditions</li><li>Material properties</li><li>Inspection history and findings</li></ul><h5>Model Validation</h5><p>Always validate predictive models using:</p><ul><li>Hold-out test data</li><li>Cross-validation</li><li>Comparison with engineering judgment</li><li>Back-testing against known outcomes</li></ul>'],
            5 => ['title' => 'Fleet Analysis', 'content' => '<h4>Fleet Analysis</h4><p>Fleet analysis examines groups of similar equipment to identify patterns and outliers.</p><h5>Applications</h5><ul><li><strong>Benchmarking:</strong> Compare corrosion rates across similar equipment in different services</li><li><strong>Outlier Detection:</strong> Identify equipment with unusually high or low corrosion rates</li><li><strong>Resource Optimization:</strong> Prioritize inspection across a fleet of equipment</li><li><strong>Life Extension:</strong> Plan systematic replacement/upgrade programs</li></ul><h5>Analysis Methods</h5><ul><li>Statistical comparison of corrosion rates by service type</li><li>Age-based reliability analysis</li><li>Weibull analysis for failure prediction</li><li>Cost optimization across the fleet</li></ul><h5>Fleet Dashboard</h5><p>The RBI Engineering Suite provides fleet views showing:</p><ul><li>Distribution of remaining life across equipment</li><li>Corrosion rate heat maps</li><li>Risk trending over time</li><li>Inspection coverage and effectiveness metrics</li></ul>'],
        ],
        'quiz' => [
            ['q' => 'Long-term corrosion rate uses:', 'options' => ['Last two readings only', 'Original to most recent', 'Estimated values only', 'Random selection'], 'answer' => 1],
            ['q' => 'Minimum data points for statistical analysis:', 'options' => ['1', '2', '3-5', '100+'], 'answer' => 2],
            ['q' => 'The remaining life formula is:', 'options' => ['RL = CR x t', 'RL = (t_actual - t_required) / CR', 'RL = t_required / CR', 'RL = CR / t_actual'], 'answer' => 1],
            ['q' => 'Which ML method identifies key corrosion factors?', 'options' => ['K-means', 'Random Forests', 'PCA', 'SVM'], 'answer' => 1],
            ['q' => 'Fleet analysis helps with:', 'options' => ['Menu planning', 'Benchmarking similar equipment', 'Weather forecasting', 'Payroll calculation'], 'answer' => 1],
            ['q' => 'Short-term rate is preferred when:', 'options' => ['Conditions are stable', 'Recent process changes occurred', 'No data is available', 'Equipment is new'], 'answer' => 1],
            ['q' => 'Half-life concept means re-inspect when RL reaches:', 'options' => ['Zero', 'Half the inspection interval', 'Twice the interval', 'Ten years'], 'answer' => 1],
            ['q' => 'Bayesian analysis incorporates:', 'options' => ['Only new data', 'Prior knowledge + new data', 'Random numbers', 'No data at all'], 'answer' => 1],
            ['q' => 'Weibull analysis is used for:', 'options' => ['Corrosion rate calculation', 'Failure prediction', 'Chemical analysis', 'Temperature measurement'], 'answer' => 1],
            ['q' => 'Model validation should include:', 'options' => ['No testing', 'Cross-validation', 'Guessing', 'Ignoring results'], 'answer' => 1],
            ['q' => 'Extreme Value Analysis examines:', 'options' => ['Average readings', 'Minimum readings for worst-case', 'Maximum temperature', 'Average pressure'], 'answer' => 1],
            ['q' => 'LSTM models are used for:', 'options' => ['Image recognition', 'Time series forecasting', 'Text translation', 'Game playing'], 'answer' => 1],
        ]
    ],
    8 => [
        'title' => 'Safety & Compliance',
        'difficulty' => 'Beginner',
        'duration' => '2 hours',
        'icon' => 'fa-hard-hat',
        'color' => 'secondary',
        'category' => 'Safety & Compliance',
        'lessons' => [
            1 => ['title' => 'OSHA PSM Requirements', 'content' => '<h4>OSHA Process Safety Management (PSM)</h4><p>OSHA 29 CFR 1910.119 - Process Safety Management of Highly Hazardous Chemicals establishes requirements for managing hazards associated with processes using highly hazardous chemicals.</p><h5>14 Elements of PSM</h5><ol><li>Employee Participation</li><li>Process Safety Information</li><li>Process Hazard Analysis</li><li>Operating Procedures</li><li>Training</li><li>Contractors</li><li>Pre-Startup Safety Review</li><li>Mechanical Integrity</li><li>Hot Work Permits</li><li>Management of Change</li><li>Incident Investigation</li><li>Emergency Planning</li><li>Compliance Audits</li><li>Trade Secrets</li></ol><h5>Mechanical Integrity (Element 8)</h5><p>This is where RBI directly supports PSM compliance:</p><ul><li>Written procedures for maintaining ongoing integrity</li><li>Inspection and testing of equipment</li><li>Correction of deficiencies</li><li>Quality assurance for equipment</li></ul>'],
            2 => ['title' => 'EPA RMP Compliance', 'content' => '<h4>EPA Risk Management Program (RMP)</h4><p>EPA 40 CFR Part 68 requires facilities with regulated substances above threshold quantities to develop Risk Management Plans.</p><h5>RMP Program Levels</h5><table class="table table-bordered"><tr><th>Level</th><th>Requirements</th></tr><tr><td>Program 1</td><td>No off-site consequence history, minimal requirements</td></tr><tr><td>Program 2</td><td>Moderate requirements for non-SIC code processes</td></tr><tr><td>Program 3</td><td>Full requirements equivalent to PSM</td></tr></table><h5>RBI Connection to RMP</h5><ul><li>Supports hazard assessment through risk quantification</li><li>Demonstrates equipment integrity management</li><li>Provides documentation for regulatory audits</li><li>Supports emergency planning through consequence analysis</li></ul>'],
            3 => ['title' => 'RAGAGEP Standards', 'content' => '<h4>Recognized and Generally Accepted Good Engineering Practices (RAGAGEP)</h4><p>RAGAGEP refers to engineering, operation, and maintenance standards that are widely adopted and recognized as good practice.</p><h5>Key RAGAGEP Standards for RBI</h5><table class="table table-bordered"><tr><th>Standard</th><th>Title</th></tr><tr><td>API 510</td><td>Pressure Vessel Inspection Code</td></tr><tr><td>API 570</td><td>Piping Inspection Code</td></tr><tr><td>API 653</td><td>Tank Inspection, Repair, Alteration, and Reconstruction</td></tr><tr><td>API 580</td><td>Risk-Based Inspection</td></tr><tr><td>API 581</td><td>RBI Methodology</td></tr><tr><td>API 571</td><td>Damage Mechanisms</td></tr><tr><td>ASME BPVC</td><td>Boiler and Pressure Vessel Code</td></tr><tr><td>NBIC</td><td>National Board Inspection Code</td></tr></table><h5>Legal Significance</h5><p>RAGAGEP standards carry legal weight under OSHA PSM as the basis for mechanical integrity programs. Deviation from RAGAGEP must be justified and documented.</p>'],
            4 => ['title' => 'Documentation Requirements', 'content' => '<h4>Documentation Requirements</h4><p>Proper documentation is essential for regulatory compliance, legal protection, and program effectiveness.</p><h5>Required Documentation</h5><ul><li><strong>RBI Assessment Records:</strong> All input data, assumptions, calculations, and results</li><li><strong>Inspection Records:</strong> Detailed findings, measurements, NDE reports, and recommendations</li><li><strong>Equipment Files:</strong> Design data, modifications, repair records, and material certifications</li><li><strong>Management of Change:</strong> Records of changes to process, equipment, or procedures</li><li><strong>Training Records:</strong> Personnel qualifications, certifications, and training history</li></ul><h5>Document Retention</h5><ul><li>Equipment files: Life of the equipment</li><li>Inspection records: Minimum two inspection cycles</li><li>RBI assessments: Current plus previous assessment</li><li>Training records: Duration of employment plus applicable retention periods</li></ul><h5>Audit Trail</h5><p>The RBI Engineering Suite automatically maintains an audit trail of all changes, assessments, and inspection activities.</p>'],
            5 => ['title' => 'Audit Preparation', 'content' => '<h4>Audit Preparation</h4><p>Facilities must be prepared for regulatory audits (OSHA, EPA, state) and internal audits.</p><h5>Common Audit Areas for RBI</h5><ul><li>RBI program documentation and procedures</li><li>Team qualifications and training records</li><li>Data quality and completeness</li><li>Assessment methodology and results</li><li>Inspection plan implementation</li><li>Follow-up on findings and recommendations</li><li>Management of Change compliance</li><li>Program effectiveness metrics</li></ul><h5>Audit Preparation Checklist</h5><ol><li>Review and update all RBI assessments</li><li>Verify inspection completion against plans</li><li>Ensure all findings have been addressed</li><li>Update training records</li><li>Review and close open action items</li><li>Verify documentation is complete and accessible</li><li>Prepare summary reports and metrics</li><li>Brief team members on audit process</li></ol><div class="alert alert-success"><i class="fas fa-clipboard-check me-2"></i><strong>Pro Tip:</strong> The RBI Engineering Suite generates audit-ready reports that compile all required documentation in a format suitable for regulatory review.</div>'],
        ],
        'quiz' => [
            ['q' => 'How many elements does OSHA PSM have?', 'options' => ['10', '12', '14', '16'], 'answer' => 2],
            ['q' => 'Which PSM element directly relates to RBI?', 'options' => ['Training', 'Mechanical Integrity', 'Hot Work', 'Trade Secrets'], 'answer' => 1],
            ['q' => 'EPA RMP is specified in which CFR?', 'options' => ['29 CFR 1910', '40 CFR Part 68', '49 CFR 171', '10 CFR 50'], 'answer' => 1],
            ['q' => 'RAGAGEP stands for:', 'options' => ['Random And General Accepted...', 'Recognized And Generally Accepted Good Engineering Practices', 'Required American Guidelines...', 'Regulated Assessment...'], 'answer' => 1],
            ['q' => 'API 510 covers:', 'options' => ['Piping inspection', 'Pressure vessel inspection', 'Tank inspection', 'Pipeline inspection'], 'answer' => 1],
            ['q' => 'Equipment files should be retained for:', 'options' => ['1 year', '5 years', '10 years', 'Life of the equipment'], 'answer' => 3],
            ['q' => 'Which is NOT part of audit preparation?', 'options' => ['Review assessments', 'Verify inspection completion', 'Delete old records', 'Update training records'], 'answer' => 2],
            ['q' => 'RMP Program 3 requirements are equivalent to:', 'options' => ['No requirements', 'Minimal requirements', 'PSM requirements', 'International standards'], 'answer' => 2],
            ['q' => 'Management of Change applies to changes in:', 'options' => ['Weather only', 'Process, equipment, or procedures', 'Employee schedules only', 'Company name only'], 'answer' => 1],
            ['q' => 'The RBI Engineering Suite maintains which feature for compliance?', 'options' => ['Game scores', 'Audit trail', 'Social media', 'Weather data'], 'answer' => 1],
        ]
    ],
];

if (!isset($allCourses[$courseId])) {
    flash('Course not found.', 'danger');
    redirect(BASE_URL . '/admin/training/');
}

$course = $allCourses[$courseId];
$pageTitle = $course['title'];

include INCLUDES_PATH . '/header.php';
?>

<style>
.course-viewer { display: flex; gap: 0; min-height: calc(100vh - 120px); margin: -24px; }
.course-sidebar {
    width: 300px;
    background: #fff;
    border-right: 1px solid #e2e8f0;
    flex-shrink: 0;
    overflow-y: auto;
    padding: 0;
}
.course-main {
    flex: 1;
    overflow-y: auto;
    padding: 32px 40px;
    max-height: calc(100vh - 120px);
}
.lesson-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
    color: #475569;
    font-size: 0.875rem;
}
.lesson-nav-item:hover { background: #f8fafc; color: #1e293b; }
.lesson-nav-item.active { background: #eff6ff; color: #1a237e; font-weight: 600; border-left: 3px solid #3f51b5; }
.lesson-nav-item.completed .lesson-check { color: #22c55e; }
.lesson-check { color: #cbd5e1; font-size: 1rem; flex-shrink: 0; }
.lesson-number { width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; }
.lesson-nav-item.active .lesson-number { background: #3f51b5; color: #fff; }

.course-progress-bar {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #fff;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}
.course-header-info {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.lesson-content { max-width: 800px; line-height: 1.8; font-size: 0.95rem; }
.lesson-content h4 { color: #1a237e; margin-bottom: 16px; font-weight: 700; }
.lesson-content h5 { color: #334155; margin-top: 24px; margin-bottom: 12px; font-weight: 600; }
.lesson-content ul, .lesson-content ol { padding-left: 20px; }
.lesson-content li { margin-bottom: 6px; }
.lesson-content table { font-size: 0.875rem; }
.lesson-content code { background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; }

.nav-buttons { display: flex; justify-content: space-between; margin-top: 40px; padding-top: 24px; border-top: 1px solid #e2e8f0; }

.quiz-container { max-width: 700px; }
.quiz-question { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
.quiz-question h6 { font-weight: 600; margin-bottom: 16px; }
.quiz-option { display: block; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s; }
.quiz-option:hover { border-color: #3f51b5; background: #f8f9ff; }
.quiz-option.selected { border-color: #3f51b5; background: #eff6ff; }
.quiz-option.correct { border-color: #22c55e; background: #f0fdf4; }
.quiz-option.incorrect { border-color: #ef4444; background: #fef2f2; }

@media (max-width: 768px) {
    .course-viewer { flex-direction: column; }
    .course-sidebar { width: 100%; max-height: 200px; }
    .course-main { padding: 20px; max-height: none; }
}
</style>

<div class="course-viewer">
    <!-- Sidebar Navigation -->
    <div class="course-sidebar">
        <div class="course-header-info">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-<?= $course['color'] ?>"><?= e($course['difficulty']) ?></span>
                <small class="text-muted"><?= e($course['duration']) ?></small>
            </div>
            <h6 class="fw-bold mb-0"><?= e($course['title']) ?></h6>
        </div>
        <div class="course-progress-bar">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted fw-semibold">Progress</small>
                <small class="text-muted" id="sidebarProgressText">0%</small>
            </div>
            <div class="progress" style="height:6px;">
                <div class="progress-bar bg-<?= $course['color'] ?>" id="sidebarProgressBar" style="width:0%;"></div>
            </div>
        </div>

        <!-- Lesson List -->
        <div id="lessonNavList">
            <?php foreach ($course['lessons'] as $lessonId => $lesson): ?>
            <div class="lesson-nav-item" data-lesson="<?= $lessonId ?>" onclick="showLesson(<?= $lessonId ?>)">
                <span class="lesson-check"><i class="fas fa-circle" id="check-<?= $lessonId ?>"></i></span>
                <span class="lesson-number"><?= $lessonId ?></span>
                <span class="flex-grow-1"><?= e($lesson['title']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="lesson-nav-item" data-lesson="quiz" onclick="showQuiz()">
                <span class="lesson-check"><i class="fas fa-circle" id="check-quiz"></i></span>
                <span class="lesson-number"><i class="fas fa-question" style="font-size:0.6rem;"></i></span>
                <span class="flex-grow-1">Final Quiz</span>
            </div>
        </div>

        <div class="p-3">
            <a href="<?= BASE_URL ?>/admin/training/" class="btn btn-outline-secondary btn-sm w-100">
                <i class="fas fa-arrow-left me-1"></i>Back to Courses
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="course-main" id="courseMainContent">
        <div class="lesson-content" id="lessonContent">
            <div class="text-center py-5">
                <i class="fas fa-<?= $course['icon'] ?> fa-4x text-<?= $course['color'] ?> mb-4" style="opacity:0.3;"></i>
                <h4>Welcome to <?= e($course['title']) ?></h4>
                <p class="text-muted">Select a lesson from the sidebar to begin, or click the button below.</p>
                <button class="btn btn-<?= $course['color'] ?> btn-lg mt-2" onclick="showLesson(1)">
                    <i class="fas fa-play me-2"></i>Start Course
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const courseId = <?= $courseId ?>;
const totalLessons = <?= count($course['lessons']) ?>;
let currentLesson = 0;

const lessonContent = <?= json_encode(array_map(function($l) { return $l['content']; }, $course['lessons'])) ?>;
const lessonTitles = <?= json_encode(array_map(function($l) { return $l['title']; }, $course['lessons'])) ?>;
const quizData = <?= json_encode($course['quiz']) ?>;

document.addEventListener('DOMContentLoaded', function() {
    updateProgress();
    // Auto-load first incomplete lesson
    const urlLesson = new URLSearchParams(window.location.search).get('lesson');
    if (urlLesson === 'quiz') showQuiz();
    else if (urlLesson) showLesson(parseInt(urlLesson));
});

function showLesson(lessonId) {
    currentLesson = lessonId;
    const content = lessonContent[lessonId];
    const title = lessonTitles[lessonId];

    let html = '<div class="lesson-content">';
    html += content;
    html += '<div class="mt-4"><button class="btn btn-success" onclick="completeLesson(' + lessonId + ')"><i class="fas fa-check me-1"></i>Mark as Complete</button></div>';
    html += '<div class="nav-buttons">';
    if (lessonId > 1) {
        html += '<button class="btn btn-outline-secondary" onclick="showLesson(' + (lessonId - 1) + ')"><i class="fas fa-arrow-left me-1"></i>Previous</button>';
    } else {
        html += '<div></div>';
    }
    if (lessonId < totalLessons) {
        html += '<button class="btn btn-primary" onclick="showLesson(' + (lessonId + 1) + ')">Next<i class="fas fa-arrow-right ms-1"></i></button>';
    } else {
        html += '<button class="btn btn-warning" onclick="showQuiz()">Take Quiz<i class="fas fa-arrow-right ms-1"></i></button>';
    }
    html += '</div></div>';

    document.getElementById('lessonContent').innerHTML = html;
    document.getElementById('courseMainContent').scrollTop = 0;

    // Update active state
    document.querySelectorAll('.lesson-nav-item').forEach(item => item.classList.remove('active'));
    document.querySelector('[data-lesson="' + lessonId + '"]').classList.add('active');
}

function completeLesson(lessonId) {
    localStorage.setItem('rbi_course_' + courseId + '_lesson_' + lessonId, 'completed');
    updateProgress();
    const checkIcon = document.getElementById('check-' + lessonId);
    if (checkIcon) {
        checkIcon.className = 'fas fa-check-circle';
        checkIcon.parentElement.classList.add('text-success');
    }
}

function showQuiz() {
    let html = '<div class="quiz-container">';
    html += '<h4 class="mb-1"><i class="fas fa-question-circle text-warning me-2"></i>Final Quiz</h4>';
    html += '<p class="text-muted mb-4">Answer the following questions. You need 70% or higher to pass.</p>';

    quizData.forEach(function(q, i) {
        html += '<div class="quiz-question" id="quizQ' + i + '">';
        html += '<h6>Question ' + (i + 1) + ': ' + q.q + '</h6>';
        q.options.forEach(function(opt, j) {
            html += '<label class="quiz-option" id="opt-' + i + '-' + j + '" onclick="selectOption(' + i + ',' + j + ')">';
            html += '<input type="radio" name="q' + i + '" value="' + j + '" class="me-2" style="display:none;">' + opt;
            html += '</label>';
        });
        html += '</div>';
    });

    html += '<div class="text-center mt-4"><button class="btn btn-lg btn-primary px-5" onclick="submitQuiz()"><i class="fas fa-paper-plane me-2"></i>Submit Quiz</button></div>';
    html += '</div>';

    document.getElementById('lessonContent').innerHTML = html;
    document.getElementById('courseMainContent').scrollTop = 0;

    document.querySelectorAll('.lesson-nav-item').forEach(item => item.classList.remove('active'));
    document.querySelector('[data-lesson="quiz"]').classList.add('active');
}

function selectOption(questionIndex, optionIndex) {
    // Clear previous selection
    for (let j = 0; j < 4; j++) {
        const el = document.getElementById('opt-' + questionIndex + '-' + j);
        if (el) el.classList.remove('selected');
    }
    document.getElementById('opt-' + questionIndex + '-' + optionIndex).classList.add('selected');
}

function submitQuiz() {
    let correct = 0;
    let total = quizData.length;
    let answered = 0;

    quizData.forEach(function(q, i) {
        let selected = -1;
        for (let j = 0; j < q.options.length; j++) {
            const el = document.getElementById('opt-' + i + '-' + j);
            if (el && el.classList.contains('selected')) {
                selected = j;
                answered++;
            }
        }

        // Show correct/incorrect
        for (let j = 0; j < q.options.length; j++) {
            const el = document.getElementById('opt-' + i + '-' + j);
            if (!el) continue;
            el.style.pointerEvents = 'none';
            if (j === q.answer) {
                el.classList.add('correct');
            } else if (j === selected && j !== q.answer) {
                el.classList.add('incorrect');
            }
        }

        if (selected === q.answer) correct++;
    });

    const score = Math.round((correct / total) * 100);
    const passed = score >= 70;

    let resultHtml = '<div class="text-center mt-4 p-4 rounded-3 ' + (passed ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10') + '">';
    resultHtml += '<h3>' + (passed ? '<i class="fas fa-trophy text-success me-2"></i>Congratulations!' : '<i class="fas fa-times-circle text-danger me-2"></i>Not Passed') + '</h3>';
    resultHtml += '<p class="fs-4 fw-bold">' + score + '% (' + correct + '/' + total + ' correct)</p>';

    if (passed) {
        localStorage.setItem('rbi_course_' + courseId + '_quiz', 'completed');
        updateProgress();
        const checkIcon = document.getElementById('check-quiz');
        if (checkIcon) { checkIcon.className = 'fas fa-check-circle'; checkIcon.parentElement.classList.add('text-success'); }

        resultHtml += '<p class="text-muted">You have successfully completed this course.</p>';
        resultHtml += '<button class="btn btn-success me-2" onclick="generateCourseCertificate()"><i class="fas fa-certificate me-1"></i>Download Certificate</button>';
    } else {
        resultHtml += '<p class="text-muted">You need 70% to pass. Please review the lessons and try again.</p>';
        resultHtml += '<button class="btn btn-primary" onclick="showQuiz()"><i class="fas fa-redo me-1"></i>Retry Quiz</button>';
    }
    resultHtml += '</div>';

    // Replace the submit button with results
    const container = document.querySelector('.quiz-container');
    container.insertAdjacentHTML('beforeend', resultHtml);
    // Remove original submit button
    const submitBtn = container.querySelector('.text-center.mt-4 .btn-primary');
    if (submitBtn) submitBtn.closest('.text-center').remove();
}

function updateProgress() {
    let completed = 0;
    const total = totalLessons + 1;

    for (let l = 1; l <= totalLessons; l++) {
        if (localStorage.getItem('rbi_course_' + courseId + '_lesson_' + l) === 'completed') {
            completed++;
            const icon = document.getElementById('check-' + l);
            if (icon) { icon.className = 'fas fa-check-circle'; icon.closest('.lesson-nav-item').querySelector('.lesson-check').style.color = '#22c55e'; }
        }
    }
    if (localStorage.getItem('rbi_course_' + courseId + '_quiz') === 'completed') {
        completed++;
        const icon = document.getElementById('check-quiz');
        if (icon) { icon.className = 'fas fa-check-circle'; icon.closest('.lesson-nav-item').querySelector('.lesson-check').style.color = '#22c55e'; }
    }

    const pct = Math.round((completed / total) * 100);
    document.getElementById('sidebarProgressBar').style.width = pct + '%';
    document.getElementById('sidebarProgressText').textContent = pct + '%';
}

function generateCourseCertificate() {
    const userName = '<?= e($_SESSION['user_name'] ?? 'User') ?>';
    const courseName = '<?= e($course['title']) ?>';
    const dateStr = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const w = window.open('', '_blank');
    w.document.write('<!DOCTYPE html><html><head><title>Certificate</title><style>@media print{.no-print{display:none}}body{font-family:Georgia,serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f1f5f9;margin:0}.cert{width:850px;background:#fff;border:3px solid #1a237e;padding:50px;text-align:center;position:relative}.cert::before{content:"";position:absolute;top:8px;left:8px;right:8px;bottom:8px;border:1px solid #c5cae9}h1{font-size:2.2rem;color:#1a237e;margin-bottom:5px}h2{font-size:1.4rem;color:#3f51b5;margin:20px 0}.name{font-size:1.8rem;border-bottom:2px solid #1a237e;display:inline-block;padding-bottom:5px;margin:15px 0}.date{color:#666;margin-top:25px}.footer{display:flex;justify-content:space-around;margin-top:35px}.sig{text-align:center}.line{width:180px;border-top:1px solid #333;margin:0 auto 5px}.sig-label{font-size:0.8rem;color:#666}</style></head><body><div><div class="cert"><div style="font-size:2.5rem;color:#1a237e">&#9733;</div><h1>Certificate of Completion</h1><p style="color:#666;letter-spacing:2px;text-transform:uppercase;font-size:0.85rem">RBI Engineering Suite Training</p><p style="color:#666">This certifies that</p><div class="name">' + userName + '</div><h2>' + courseName + '</h2><p style="color:#666;font-size:0.9rem">has demonstrated proficiency in the above subject matter</p><p class="date">' + dateStr + '</p><div class="footer"><div class="sig"><div class="line"></div><div class="sig-label">Program Director</div></div><div class="sig"><div class="line"></div><div class="sig-label">Chief Inspector</div></div></div></div><div class="text-center mt-3 no-print"><button onclick="window.print()" style="padding:10px 30px;background:#1a237e;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:1rem">Print / Save as PDF</button></div></div></body></html>');
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
