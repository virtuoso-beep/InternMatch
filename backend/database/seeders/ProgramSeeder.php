<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Starter vocabulary, not an approved curriculum or evidence of student attainment.
        $catalog = [
            ['BSA', 'Bachelor of Science in Accountancy', 'Accountancy & Business', 'Financial Accounting|Auditing|Bookkeeping|Taxation|Cost Accounting|Financial Reporting|Bank Reconciliation|Accounts Payable|Accounts Receivable|Payroll Accounting|Internal Controls|Accounting Information Systems|Spreadsheet Analysis|Budgeting|Professional Ethics'],
            ['BSMA', 'Bachelor of Science in Management Accounting', 'Accountancy & Business', 'Management Accounting|Cost Accounting|Budgeting|Financial Analysis|Variance Analysis|Financial Planning|Business Analysis|Spreadsheet Analysis|Inventory Accounting|Performance Measurement|Internal Controls|Risk Management|Data Analysis|Accounting Information Systems|Professional Ethics'],
            ['BSPSYCH', 'Bachelor of Science in Psychology', 'Arts & Sciences', 'Psychological Research|Behavioral Observation|Interviewing|Counseling Support|Case Documentation|Research Ethics|Psychological Statistics|Survey Design|Data Analysis|Human Development|Group Facilitation|Mental Health Awareness|Assessment Support|Active Listening|Report Writing'],
            ['ABENG', 'Bachelor of Arts in English Language', 'Arts & Sciences', 'Technical Writing|Copyediting|ESL Instruction|Proofreading|Academic Writing|Content Writing|Public Speaking|Linguistic Analysis|Research Writing|Lesson Planning|Reading Comprehension|Intercultural Communication|Translation|Documentation|Digital Publishing'],
            ['BSBA-HRM', 'BS Business Administration – Human Resource Management', 'Accountancy & Business', 'Recruitment Support|Employee Relations|Training Coordination|HR Documentation|Job Analysis|Performance Management|Compensation Administration|Labor Relations|Interviewing|Organizational Behavior|Personnel Records|Spreadsheet Analysis|Business Communication|Conflict Resolution|Professional Ethics'],
            ['BSBA-MM', 'BS Business Administration – Marketing Management', 'Accountancy & Business', 'Market Research|Digital Marketing|Content Marketing|Consumer Behavior|Brand Management|Sales Support|Customer Relations|Social Media Management|Marketing Analytics|Campaign Planning|Business Communication|Presentation Skills|Survey Design|Product Research|Spreadsheet Analysis'],
            ['BSBA-FM', 'BS Business Administration – Financial Management', 'Accountancy & Business', 'Financial Analysis|Budgeting|Investment Analysis|Financial Planning|Risk Management|Credit Analysis|Cash Management|Banking Operations|Financial Reporting|Spreadsheet Analysis|Business Valuation|Data Analysis|Business Communication|Portfolio Analysis|Professional Ethics'],
            ['BSCS', 'Bachelor of Science in Computer Science', 'Computing', 'Programming|Algorithms|Data Structures|Database Design|Software Engineering|Software Testing|Web Development|Machine Learning Fundamentals|Data Analysis|Version Control|Operating Systems|Computer Networks|Technical Documentation|Problem Solving|REST APIs'],
            ['BSIT', 'Bachelor of Science in Information Technology', 'Computing', 'Web Development|Programming|Database Management|Networking|Technical Support|Systems Analysis|PHP|Laravel|JavaScript|HTML and CSS|Software Testing|Version Control|Information Security|Technical Documentation|REST APIs'],
            ['BSCRIM', 'Bachelor of Science in Criminology', 'Criminology', 'Law Enforcement Procedures|Investigation Support|Evidence Documentation|Forensic Awareness|Crime Prevention|Incident Reporting|Community Relations|Criminal Law Fundamentals|Public Safety|Research Methods|Interviewing|Records Management|Professional Ethics|Observation Skills|Report Writing'],
            ['BSEE', 'Bachelor of Science in Electrical Engineering', 'Engineering', 'Circuit Analysis|Electrical Design|Power Systems|Electrical Measurements|Control Systems|AutoCAD|Electrical Safety|Equipment Maintenance|Technical Drawing|Instrumentation|Troubleshooting|Project Documentation|Renewable Energy Fundamentals|Engineering Mathematics|Professional Ethics'],
            ['BSCOE', 'Bachelor of Science in Computer Engineering', 'Engineering', 'Digital Logic|Embedded Systems|Circuit Design|Microcontrollers|Computer Networks|Programming|Hardware Troubleshooting|PCB Layout|Embedded C|Software Testing|Systems Integration|Technical Documentation|Signal Processing|Electronics Measurements|Computer Architecture'],
            ['BSECE', 'Bachelor of Science in Electronics Engineering', 'Engineering', 'Circuit Design|PCB Layout|Embedded C|Analog Electronics|Digital Electronics|Signal Processing|Communication Systems|Instrumentation|Microcontrollers|Electronics Measurements|Control Systems|Technical Documentation|Troubleshooting|Engineering Mathematics|Electronics Safety'],
            ['BSED', 'Bachelor of Secondary Education', 'Education', 'Lesson Planning|Classroom Management|Instructional Design|Student Assessment|Teaching Demonstration|Educational Technology|Learning Materials Development|Inclusive Education|Subject Content Delivery|Classroom Observation|Reflective Practice|Educational Research|Communication Skills|Rubric Development|Professional Ethics'],
            ['BPED', 'Bachelor of Physical Education', 'Education', 'Physical Education Instruction|Lesson Planning|Sports Coaching|Fitness Assessment|Motor Learning|Classroom Management|Sports Officiating|Exercise Planning|Dance Instruction|Health Education|Safety and First Aid|Inclusive Physical Education|Student Assessment|Activity Organization|Reflective Practice'],
            ['BEED', 'Bachelor of Elementary Education', 'Education', 'Lesson Planning|Classroom Management|Early Literacy|Numeracy Instruction|Child Development|Learning Materials Development|Inclusive Education|Student Assessment|Educational Technology|Teaching Demonstration|Classroom Observation|Parent Communication|Reflective Practice|Instructional Design|Professional Ethics'],
        ];
        foreach ($catalog as [$code, $name, $cluster, $vocabulary]) {
            $program = Program::firstOrCreate(['code' => $code], ['name' => $name, 'cluster' => $cluster]);
            if ($program->cluster === null) {
                $program->update(['cluster' => $cluster]);
            }
            foreach (explode('|', $vocabulary) as $label) {
                $competency = Competency::firstOrCreate(['code' => Str::slug($label)], ['name' => $label]);
                $program->competencies()->syncWithoutDetaching([$competency->id]);
            }
        }
    }
}
