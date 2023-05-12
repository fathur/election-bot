import math
import os
import sentry_sdk

import click
import tweepy

from app.services.poll_maker import PollMaker
from app.services.reporting import Reporting


@click.group()
def run():
    pass


@run.command()
@click.option(
    "-k",
    "--kind",
    required=True,
    show_default=True,
    type=click.Choice(["candidate", "media"], case_sensitive=True),
)
def create_poll(kind):
    click.echo("Executing create poll")
    PollMaker.run(kind)
    click.echo("Executed create poll")


@run.command()
@click.option(
    "-i",
    "--interval",
    required=True,
    show_default=True,
    type=click.Choice(
        ["daily", "weekly", "monthly", "quarterly", "yearly"], case_sensitive=True
    ),
)
def generate_report(interval):
    click.echo(f"Executing generate report {interval}")
    Reporting.run(interval)
    click.echo(f"Executed generate report {interval}")
